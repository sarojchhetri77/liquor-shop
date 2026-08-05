<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="grid min-h-svh lg:grid-cols-2">
        <!-- Branded panel -->
        <div
            class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-neutral-800 via-neutral-950 to-black p-12 text-white lg:flex"
        >
            <Link :href="home()" class="flex items-center gap-2.5">
                <span
                    class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-md bg-white"
                >
                    <img src="/pb_store_logo.jpeg" alt="PB Store" class="size-full object-contain" />
                </span>
                <span class="font-display text-2xl font-semibold tracking-tight">
                    PB Store
                </span>
            </Link>

            <div class="my-auto max-w-md space-y-4">
                <h2 class="font-display text-4xl leading-[1.1] font-semibold lg:text-5xl">
                    Fine wine &amp; spirits, delivered.
                </h2>
                <p class="text-base leading-relaxed text-white/70">
                    Imadol, Lalitpur — a curated cellar of hand-picked wines,
                    premium spirits and ice-cold beer, brought to your door.
                </p>
            </div>

            <p class="text-xs text-white/40">
                You must be 21+ to purchase · Please drink responsibly
            </p>
        </div>

        <!-- Form panel -->
        <div
            class="flex flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
        >
            <div class="w-full max-w-sm">
                <Link
                    :href="home()"
                    class="mb-8 flex items-center justify-center gap-2 lg:hidden"
                >
                    <span
                        class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-md bg-white"
                    >
                        <img src="/pb_store_logo.jpeg" alt="PB Store" class="size-full object-contain" />
                    </span>
                    <span class="font-display text-xl font-semibold">PB Store</span>
                </Link>

                <div class="flex flex-col gap-8">
                    <div class="space-y-2 text-center">
                        <h1 class="font-display text-2xl font-semibold">
                            {{ title }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
