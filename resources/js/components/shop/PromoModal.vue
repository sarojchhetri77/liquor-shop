<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, X } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { Promotion } from '@/types/shop';

/** How long each slide stays up before the slider advances on its own. */
const AUTOPLAY_MS = 5000;

const page = usePage();
const promotions = computed(
    () => (page.props.promotions as Promotion[] | undefined) ?? [],
);

const open = ref(false);
const current = ref(0);
let autoplayTimer: ReturnType<typeof setInterval> | undefined;

const hasSlider = computed(() => promotions.value.length > 1);
const active = computed(() => promotions.value[current.value] ?? null);

/** One key for the whole set, so the popup is shown once per session. */
const storageKey = computed(() =>
    promotions.value.length > 0
        ? `promo_seen_${promotions.value.map((promotion) => promotion.id).join('-')}`
        : '',
);

onMounted(() => {
    if (promotions.value.length === 0) {
        return;
    }

    // Show once per browser session so it isn't repeated on every navigation.
    if (!sessionStorage.getItem(storageKey.value)) {
        open.value = true;
    }
});

watch([open, hasSlider], () => {
    stopAutoplay();

    if (open.value && hasSlider.value) {
        autoplayTimer = setInterval(next, AUTOPLAY_MS);
    }
});

function stopAutoplay(): void {
    clearInterval(autoplayTimer);
    autoplayTimer = undefined;
}

/** Restart the countdown after a manual move, so it doesn't jump immediately. */
function restartAutoplay(): void {
    if (!hasSlider.value) {
        return;
    }

    stopAutoplay();
    autoplayTimer = setInterval(next, AUTOPLAY_MS);
}

function next(): void {
    if (promotions.value.length === 0) {
        return;
    }

    current.value = (current.value + 1) % promotions.value.length;
}

function previous(): void {
    if (promotions.value.length === 0) {
        return;
    }

    current.value =
        (current.value - 1 + promotions.value.length) % promotions.value.length;
}

function go(index: number): void {
    current.value = index;
    restartAutoplay();
}

function step(direction: number): void {
    if (direction > 0) {
        next();
    } else {
        previous();
    }

    restartAutoplay();
}

function dismiss(): void {
    open.value = false;
    stopAutoplay();

    if (storageKey.value) {
        sessionStorage.setItem(storageKey.value, '1');
    }
}

function onKeydown(event: KeyboardEvent): void {
    if (!open.value) {
        return;
    }

    if (event.key === 'Escape') {
        dismiss();
    } else if (event.key === 'ArrowRight' && hasSlider.value) {
        step(1);
    } else if (event.key === 'ArrowLeft' && hasSlider.value) {
        step(-1);
    }
}

onMounted(() => document.addEventListener('keydown', onKeydown));

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    stopAutoplay();
});
</script>

<template>
    <Teleport to="body">
        <Transition name="promo-fade">
            <div
                v-if="open && active"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                aria-label="Promotions"
                @click.self="dismiss"
            >
                <div class="relative w-full max-w-2xl">
                    <button
                        class="absolute -top-3 -right-3 z-20 flex size-9 items-center justify-center rounded-full bg-background text-foreground shadow-lg transition-transform hover:scale-105"
                        aria-label="Close"
                        @click="dismiss"
                    >
                        <X class="size-5" />
                    </button>

                    <div class="overflow-hidden rounded-2xl bg-card shadow-2xl">
                        <div class="relative">
                            <!-- Slides: only the active one is mounted, cross-faded. -->
                            <Transition name="promo-slide" mode="out-in">
                                <component
                                    :is="active.link ? Link : 'div'"
                                    :key="active.id"
                                    :href="active.link || undefined"
                                    class="block"
                                    @click="active.link ? dismiss() : null"
                                >
                                    <img
                                        :src="active.image_url"
                                        :alt="active.title"
                                        class="max-h-[70vh] w-full object-contain"
                                    />
                                </component>
                            </Transition>

                            <template v-if="hasSlider">
                                <button
                                    class="absolute top-1/2 left-2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-background/80 text-foreground shadow-md backdrop-blur transition-colors hover:bg-background"
                                    aria-label="Previous promotion"
                                    @click.stop="step(-1)"
                                >
                                    <ChevronLeft class="size-5" />
                                </button>
                                <button
                                    class="absolute top-1/2 right-2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-background/80 text-foreground shadow-md backdrop-blur transition-colors hover:bg-background"
                                    aria-label="Next promotion"
                                    @click.stop="step(1)"
                                >
                                    <ChevronRight class="size-5" />
                                </button>
                            </template>
                        </div>

                        <div class="border-t bg-card px-5 py-3 text-center">
                            <p class="text-sm font-medium text-muted-foreground">
                                {{ active.title }}
                            </p>

                            <div v-if="hasSlider" class="mt-2.5 flex items-center justify-center gap-1.5">
                                <button
                                    v-for="(promotion, index) in promotions"
                                    :key="promotion.id"
                                    :aria-label="`Show promotion ${index + 1}`"
                                    :aria-current="index === current"
                                    :class="[
                                        'h-1.5 rounded-full transition-all',
                                        index === current
                                            ? 'w-5 bg-primary'
                                            : 'w-1.5 bg-muted-foreground/40 hover:bg-muted-foreground/70',
                                    ]"
                                    @click="go(index)"
                                ></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.promo-fade-enter-active,
.promo-fade-leave-active {
    transition: opacity 0.2s ease;
}
.promo-fade-enter-from,
.promo-fade-leave-to {
    opacity: 0;
}
.promo-slide-enter-active,
.promo-slide-leave-active {
    transition: opacity 0.25s ease;
}
.promo-slide-enter-from,
.promo-slide-leave-to {
    opacity: 0;
}
</style>
