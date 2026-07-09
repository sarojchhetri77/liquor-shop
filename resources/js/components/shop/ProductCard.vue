<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ShoppingCart } from '@lucide/vue';
import { computed } from 'vue';
import BottleThumb from '@/components/shop/BottleThumb.vue';
import StarRating from '@/components/shop/StarRating.vue';
import { formatMoney } from '@/lib/format';
import type { Product } from '@/types/shop';

const props = defineProps<{
    product: Product;
    canBuy?: boolean;
}>();

const image = computed(() => props.product.images?.[0]?.url ?? null);
const hasDiscount = computed(() => props.product.discount_percent > 0);
const soldOut = computed(() => props.product.stock <= 0);

function addToCart(): void {
    router.post(
        `/cart/${props.product.id}`,
        { quantity: 1 },
        { preserveScroll: true },
    );
}
</script>

<template>
    <div
        class="group relative flex flex-col overflow-hidden rounded-2xl border bg-card transition-all duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-[0_18px_44px_-20px_rgba(74,10,30,0.45)]"
    >
        <Link
            :href="`/products/${product.slug}`"
            class="relative block aspect-[4/5] overflow-hidden bg-secondary/50"
        >
            <img
                v-if="image"
                :src="image"
                :alt="product.name"
                loading="lazy"
                class="h-full w-full object-cover transition-transform duration-[600ms] ease-out group-hover:scale-105"
            />
            <BottleThumb
                v-else
                :name="product.name"
                :category="product.category?.name"
                class="transition-transform duration-[600ms] ease-out group-hover:scale-105"
            />

            <span
                v-if="hasDiscount"
                class="absolute top-3 left-0 rounded-r-full bg-gold px-3 py-1 text-[11px] font-semibold tracking-wide text-white uppercase shadow-md"
            >
                {{ product.discount_percent }}% off
            </span>
            <span
                v-if="soldOut"
                class="absolute inset-0 flex items-center justify-center bg-background/55 text-xs font-semibold tracking-[0.22em] text-foreground uppercase backdrop-blur-[1px]"
            >
                Sold out
            </span>
        </Link>

        <div class="flex flex-1 flex-col p-4">
            <p
                v-if="product.brand"
                class="mb-1 text-[11px] font-semibold tracking-[0.14em] text-primary/70 uppercase"
            >
                {{ product.brand }}
            </p>
            <Link
                :href="`/products/${product.slug}`"
                class="font-display line-clamp-2 text-lg leading-snug font-medium transition-colors group-hover:text-primary"
            >
                {{ product.name }}
            </Link>

            <div class="mt-1.5 flex items-center gap-1.5">
                <StarRating :rating="Number(product.rating)" :size="13" />
                <span class="text-xs text-muted-foreground">({{ product.reviews_count }})</span>
            </div>

            <div class="mt-4 flex items-end justify-between gap-2">
                <div class="flex flex-col leading-none">
                    <span
                        v-if="hasDiscount"
                        class="mb-0.5 text-xs text-muted-foreground line-through"
                    >
                        {{ formatMoney(product.price) }}
                    </span>
                    <span class="font-display text-xl font-semibold">{{ formatMoney(product.final_price) }}</span>
                </div>

                <button
                    v-if="canBuy && !soldOut"
                    type="button"
                    aria-label="Add to cart"
                    class="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-sm transition-transform hover:scale-105 active:scale-95"
                    @click="addToCart"
                >
                    <ShoppingCart :size="17" />
                </button>
            </div>
        </div>
    </div>
</template>
