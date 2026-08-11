<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import BottleThumb from '@/components/shop/BottleThumb.vue';
import { Spinner } from '@/components/ui/spinner';
import { formatMoney } from '@/lib/format';
import { index as products, show as productShow } from '@/routes/shop/products';
import { suggestions as suggestionsRoute } from '@/routes/shop/search';

type Suggestion = {
    id: number;
    name: string;
    slug: string;
    brand: string | null;
    category: string | null;
    price: string;
    final_price: number;
    is_discount_active: boolean;
    image: string | null;
};

/** Shorter terms match almost everything, so suggestions only start at two characters. */
const MIN_QUERY_LENGTH = 2;
/** How long the typing has to pause before the suggestions are requested. */
const DEBOUNCE_MS = 250;

const search = ref(
    new URLSearchParams(
        typeof window !== 'undefined' ? window.location.search : '',
    ).get('search') ?? '',
);

const suggestions = ref<Suggestion[]>([]);
const isOpen = ref(false);
const isLoading = ref(false);
const hasFailed = ref(false);
const activeIndex = ref(-1);
const root = ref<HTMLElement | null>(null);

let debounceTimer: ReturnType<typeof setTimeout> | undefined;
let inFlight: AbortController | null = null;

const term = computed(() => search.value.trim());
const canSuggest = computed(() => term.value.length >= MIN_QUERY_LENGTH);
const hasPanel = computed(() => isOpen.value && canSuggest.value);

watch(search, () => {
    clearTimeout(debounceTimer);
    activeIndex.value = -1;

    if (!canSuggest.value) {
        inFlight?.abort();
        inFlight = null;
        suggestions.value = [];
        isLoading.value = false;
        hasFailed.value = false;
        isOpen.value = false;

        return;
    }

    isOpen.value = true;
    isLoading.value = true;
    hasFailed.value = false;
    debounceTimer = setTimeout(fetchSuggestions, DEBOUNCE_MS);
});

async function fetchSuggestions(): Promise<void> {
    const requested = term.value;

    inFlight?.abort();
    inFlight = new AbortController();

    try {
        const response = await fetch(
            suggestionsRoute.url({ query: { search: requested } }),
            {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: inFlight.signal,
            },
        );

        if (!response.ok) {
            throw new Error(`Suggestions request failed: ${response.status}`);
        }

        const payload = (await response.json()) as { products: Suggestion[] };

        // A newer keystroke may have landed while this request was in flight.
        if (requested !== term.value) {
            return;
        }

        suggestions.value = payload.products;
        isLoading.value = false;
    } catch (error) {
        if ((error as Error).name === 'AbortError') {
            return;
        }

        // Never pass a failed lookup off as "no matches".
        suggestions.value = [];
        isLoading.value = false;
        hasFailed.value = true;
    }
}

function submitSearch(): void {
    close();
    router.get(
        products.url(),
        { search: term.value || undefined },
        { preserveState: true },
    );
}

function visit(suggestion: Suggestion): void {
    close();
    router.visit(productShow.url(suggestion.slug));
}

function onEnter(): void {
    const active = suggestions.value[activeIndex.value];

    if (active) {
        visit(active);

        return;
    }

    submitSearch();
}

function move(step: number): void {
    if (suggestions.value.length === 0) {
        return;
    }

    isOpen.value = true;
    const next = activeIndex.value + step;
    activeIndex.value =
        next < -1
            ? suggestions.value.length - 1
            : next >= suggestions.value.length
              ? -1
              : next;
}

function close(): void {
    isOpen.value = false;
    activeIndex.value = -1;
}

function onFocus(): void {
    if (canSuggest.value && suggestions.value.length > 0) {
        isOpen.value = true;
    }
}

function onPointerDown(event: MouseEvent): void {
    if (root.value && !root.value.contains(event.target as Node)) {
        close();
    }
}

/** Split a label so the part matching the query can be emphasised. */
function segments(label: string): { text: string; match: boolean }[] {
    if (term.value === '') {
        return [{ text: label, match: false }];
    }

    const escaped = term.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    return label
        .split(new RegExp(`(${escaped})`, 'ig'))
        .filter((part) => part !== '')
        .map((part) => ({
            text: part,
            match: part.toLowerCase() === term.value.toLowerCase(),
        }));
}

onMounted(() => document.addEventListener('mousedown', onPointerDown));

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onPointerDown);
    clearTimeout(debounceTimer);
    inFlight?.abort();
});
</script>

<template>
    <div ref="root" class="relative">
        <form class="relative" role="search" @submit.prevent="onEnter">
            <Search
                class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <input
                v-model="search"
                type="search"
                placeholder="Search the cellar…"
                autocomplete="off"
                role="combobox"
                aria-label="Search products"
                :aria-expanded="hasPanel"
                aria-controls="search-suggestions"
                class="h-10 w-full rounded-full border bg-card pr-9 pl-9 text-sm transition-colors outline-none focus:border-primary"
                @focus="onFocus"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.esc="close"
            />
            <Spinner
                v-if="isLoading"
                class="absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
        </form>

        <div
            v-if="hasPanel"
            id="search-suggestions"
            role="listbox"
            class="absolute inset-x-0 top-[calc(100%+0.5rem)] z-50 overflow-hidden rounded-2xl border bg-card shadow-2xl"
        >
            <p
                v-if="isLoading && suggestions.length === 0"
                class="px-4 py-6 text-center text-sm text-muted-foreground"
            >
                Searching…
            </p>

            <p
                v-else-if="hasFailed"
                class="px-4 py-6 text-center text-sm text-muted-foreground"
            >
                Suggestions are unavailable — press Enter to search.
            </p>

            <p
                v-else-if="suggestions.length === 0"
                class="px-4 py-6 text-center text-sm text-muted-foreground"
            >
                Nothing matches “{{ term }}”.
            </p>

            <ul v-else class="max-h-[22rem] overflow-y-auto py-1">
                <li v-for="(suggestion, index) in suggestions" :key="suggestion.id">
                    <button
                        type="button"
                        role="option"
                        :aria-selected="index === activeIndex"
                        :class="[
                            'flex w-full items-center gap-3 px-3 py-2.5 text-left transition-colors',
                            index === activeIndex ? 'bg-accent' : 'hover:bg-accent/60',
                        ]"
                        @mouseenter="activeIndex = index"
                        @click="visit(suggestion)"
                    >
                        <span
                            class="size-11 shrink-0 overflow-hidden rounded-lg bg-secondary/50"
                        >
                            <img
                                v-if="suggestion.image"
                                :src="suggestion.image"
                                :alt="suggestion.name"
                                class="size-full object-cover"
                            />
                            <BottleThumb
                                v-else
                                :name="suggestion.name"
                                :category="suggestion.category"
                            />
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm">
                                <span
                                    v-for="(part, i) in segments(suggestion.name)"
                                    :key="i"
                                    :class="part.match ? 'font-semibold text-foreground' : ''"
                                >{{ part.text }}</span>
                            </span>
                            <span
                                v-if="suggestion.brand || suggestion.category"
                                class="block truncate text-[11px] tracking-[0.12em] text-muted-foreground uppercase"
                            >
                                {{ [suggestion.brand, suggestion.category].filter(Boolean).join(' · ') }}
                            </span>
                        </span>

                        <span class="shrink-0 text-sm font-semibold">
                            {{ formatMoney(suggestion.final_price) }}
                        </span>
                    </button>
                </li>
            </ul>

            <button
                v-if="suggestions.length > 0"
                type="button"
                class="flex w-full items-center gap-2 border-t px-4 py-2.5 text-left text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase transition-colors hover:bg-accent/60 hover:text-foreground"
                @click="submitSearch"
            >
                <Search class="size-3.5" /> See all results for “{{ term }}”
            </button>
        </div>
    </div>
</template>
