<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Plus, Search, X } from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { store as storeBrand } from '@/routes/admin/brands';
import type { Brand } from '@/types/shop';

const props = defineProps<{
    modelValue: number | string;
    brands: Brand[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number | string];
}>();

const controlClass =
    'h-11 w-full rounded-lg border bg-background px-3.5 text-sm outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/15';

const options = ref<Brand[]>([...props.brands]);
const isOpen = ref(false);
const query = ref('');
const activeIndex = ref(-1);
const addingBrand = ref(false);

const root = ref<HTMLElement | null>(null);
const searchInput = ref<HTMLInputElement | null>(null);
const newBrandInput = ref<HTMLInputElement | null>(null);

const brandForm = useHttp<{ name: string }, { brand: Brand }>({ name: '' });

const selected = computed(
    () => options.value.find((brand) => brand.id === Number(props.modelValue)) ?? null,
);

const filtered = computed(() => {
    const needle = query.value.trim().toLowerCase();

    return needle === ''
        ? options.value
        : options.value.filter((brand) => brand.name.toLowerCase().includes(needle));
});

/** A brand can be created straight from the search box unless it already exists. */
const canCreateFromQuery = computed(() => {
    const needle = query.value.trim().toLowerCase();

    return (
        needle !== '' &&
        !options.value.some((brand) => brand.name.toLowerCase() === needle)
    );
});

async function open(): Promise<void> {
    isOpen.value = true;
    query.value = '';
    activeIndex.value = -1;
    await nextTick();
    searchInput.value?.focus();
}

function close(): void {
    isOpen.value = false;
    activeIndex.value = -1;
}

function toggle(): void {
    if (isOpen.value) {
        close();

        return;
    }

    void open();
}

function select(brand: Brand | null): void {
    emit('update:modelValue', brand?.id ?? '');
    close();
}

function move(step: number): void {
    if (filtered.value.length === 0) {
        return;
    }

    const next = activeIndex.value + step;
    activeIndex.value =
        next < 0
            ? filtered.value.length - 1
            : next >= filtered.value.length
              ? 0
              : next;
}

function onEnter(): void {
    const active = filtered.value[activeIndex.value];

    if (active) {
        select(active);

        return;
    }

    if (canCreateFromQuery.value) {
        void createBrand(query.value);
    }
}

async function openAddField(): Promise<void> {
    close();
    addingBrand.value = true;
    brandForm.clearErrors();
    await nextTick();
    newBrandInput.value?.focus();
}

function cancelAddField(): void {
    addingBrand.value = false;
    brandForm.reset();
    brandForm.clearErrors();
}

/** Create a brand, then drop it into the list and select it. */
async function createBrand(name: string): Promise<void> {
    if (brandForm.processing || name.trim() === '') {
        return;
    }

    brandForm.name = name.trim();

    try {
        const { brand } = await brandForm.post(storeBrand.url());

        if (!options.value.some((option) => option.id === brand.id)) {
            options.value = [...options.value, brand].sort((a, b) =>
                a.name.localeCompare(b.name),
            );
        }

        emit('update:modelValue', brand.id);
        cancelAddField();
        close();
    } catch {
        // Validation errors are surfaced through brandForm.errors.
    }
}

function onPointerDown(event: MouseEvent): void {
    if (root.value && !root.value.contains(event.target as Node)) {
        close();
    }
}

onMounted(() => document.addEventListener('mousedown', onPointerDown));
onBeforeUnmount(() => document.removeEventListener('mousedown', onPointerDown));
</script>

<template>
    <div ref="root" class="relative">
        <div class="flex items-center gap-2">
            <button
                id="brand"
                type="button"
                role="combobox"
                :aria-expanded="isOpen"
                aria-controls="brand-options"
                :class="[controlClass, 'flex items-center justify-between gap-2 text-left']"
                @click="toggle"
                @keydown.down.prevent="open"
            >
                <span :class="selected ? 'truncate' : 'truncate text-muted-foreground'">
                    {{ selected?.name ?? 'No brand' }}
                </span>
                <ChevronsUpDown class="size-4 shrink-0 opacity-50" />
            </button>
            <button
                type="button"
                title="Add a new brand"
                aria-label="Add a new brand"
                class="flex size-11 shrink-0 items-center justify-center rounded-lg border text-muted-foreground transition-colors hover:border-primary hover:bg-accent/40 hover:text-primary"
                @click="openAddField"
            >
                <Plus class="size-4" />
            </button>
        </div>

        <!-- Searchable options -->
        <div
            v-if="isOpen"
            id="brand-options"
            class="absolute inset-x-0 top-[calc(100%+0.35rem)] z-50 overflow-hidden rounded-lg border bg-card shadow-2xl"
        >
            <div class="relative border-b p-2">
                <Search class="absolute top-1/2 left-4 size-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    ref="searchInput"
                    v-model="query"
                    type="search"
                    placeholder="Search brands…"
                    autocomplete="off"
                    aria-label="Search brands"
                    class="h-9 w-full rounded-md border bg-background pr-3 pl-8 text-sm outline-none focus:border-primary"
                    @keydown.down.prevent="move(1)"
                    @keydown.up.prevent="move(-1)"
                    @keydown.enter.prevent="onEnter"
                    @keydown.esc="close"
                />
            </div>

            <ul role="listbox" class="max-h-60 overflow-y-auto py-1">
                <li v-if="query.trim() === ''">
                    <button
                        type="button"
                        role="option"
                        :aria-selected="!selected"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-muted-foreground transition-colors hover:bg-accent/60"
                        @click="select(null)"
                    >
                        <Check :class="['size-4', selected ? 'opacity-0' : '']" />
                        No brand
                    </button>
                </li>
                <li v-for="(brand, index) in filtered" :key="brand.id">
                    <button
                        type="button"
                        role="option"
                        :aria-selected="brand.id === Number(modelValue)"
                        :class="[
                            'flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition-colors',
                            index === activeIndex ? 'bg-accent' : 'hover:bg-accent/60',
                        ]"
                        @mouseenter="activeIndex = index"
                        @click="select(brand)"
                    >
                        <Check
                            :class="['size-4', brand.id === Number(modelValue) ? 'text-primary' : 'opacity-0']"
                        />
                        <span class="truncate">{{ brand.name }}</span>
                    </button>
                </li>
                <li
                    v-if="filtered.length === 0 && !canCreateFromQuery"
                    class="px-3 py-4 text-center text-sm text-muted-foreground"
                >
                    No brands found.
                </li>
            </ul>

            <button
                v-if="canCreateFromQuery"
                type="button"
                :disabled="brandForm.processing"
                class="flex w-full items-center gap-2 border-t px-3 py-2.5 text-left text-sm font-medium text-primary transition-colors hover:bg-accent/60 disabled:cursor-wait"
                @click="createBrand(query)"
            >
                <Spinner v-if="brandForm.processing" class="size-4" />
                <Plus v-else class="size-4" />
                Add “{{ query.trim() }}” as a new brand
            </button>
        </div>

        <!-- Explicit "new brand" field, opened with the + button -->
        <div v-if="addingBrand" class="mt-2 flex items-center gap-2">
            <input
                ref="newBrandInput"
                v-model="brandForm.name"
                :class="controlClass"
                placeholder="New brand name"
                @keydown.enter.prevent="createBrand(brandForm.name)"
                @keydown.esc="cancelAddField"
            />
            <button
                type="button"
                title="Save brand"
                aria-label="Save brand"
                :disabled="brandForm.processing"
                class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground transition-opacity hover:opacity-90 disabled:cursor-wait"
                @click="createBrand(brandForm.name)"
            >
                <Spinner v-if="brandForm.processing" class="size-4" />
                <Check v-else class="size-4" />
            </button>
            <button
                type="button"
                title="Cancel"
                aria-label="Cancel adding a brand"
                class="flex size-11 shrink-0 items-center justify-center rounded-lg border text-muted-foreground transition-colors hover:text-foreground"
                @click="cancelAddField"
            >
                <X class="size-4" />
            </button>
        </div>

        <InputError class="mt-1.5" :message="brandForm.errors.name" />
    </div>
</template>
