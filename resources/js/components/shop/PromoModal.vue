<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import type { Promotion } from '@/types/shop';

const page = usePage();
const promotion = computed(() => page.props.promotion as Promotion | null | undefined);
const open = ref(false);

const storageKey = computed(() =>
    promotion.value ? `promo_seen_${promotion.value.id}` : '',
);

onMounted(() => {
    if (!promotion.value) {
        return;
    }

    // Show once per browser session so it isn't repeated on every navigation.
    const seen = sessionStorage.getItem(storageKey.value);

    if (!seen) {
        open.value = true;
    }
});

function dismiss(): void {
    open.value = false;

    if (storageKey.value) {
        sessionStorage.setItem(storageKey.value, '1');
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="promo-fade">
            <div
                v-if="open && promotion"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
                @click.self="dismiss"
            >
                <div class="relative w-full max-w-2xl">
                    <button
                        class="absolute -top-3 -right-3 z-10 flex size-9 items-center justify-center rounded-full bg-background text-foreground shadow-lg transition-transform hover:scale-105"
                        aria-label="Close"
                        @click="dismiss"
                    >
                        <X class="size-5" />
                    </button>

                    <component
                        :is="promotion.link ? Link : 'div'"
                        :href="promotion.link || undefined"
                        class="block overflow-hidden rounded-2xl bg-card shadow-2xl"
                        @click="promotion.link ? dismiss() : null"
                    >
                        <img
                            :src="promotion.image_url"
                            :alt="promotion.title"
                            class="max-h-[75vh] w-full object-contain"
                        />
                        <p
                            class="border-t bg-card px-5 py-3 text-center text-sm font-medium text-muted-foreground"
                        >
                            {{ promotion.title }}
                        </p>
                    </component>
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
</style>
