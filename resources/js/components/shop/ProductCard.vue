<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Check, ShoppingCart } from '@lucide/vue';
import { computed, ref } from 'vue';
import BottleThumb from '@/components/shop/BottleThumb.vue';
import StarRating from '@/components/shop/StarRating.vue';
import { Spinner } from '@/components/ui/spinner';
import { formatMoney } from '@/lib/format';
import { login } from '@/routes';
import { store as addToCartRoute } from '@/routes/shop/cart';
import { show as productShow } from '@/routes/shop/products';
import type { Product } from '@/types/shop';

const props = defineProps<{
    product: Product;
    canBuy?: boolean;
}>();

const image = computed(() => props.product.images?.[0]?.url ?? null);
const hasDiscount = computed(() => props.product.is_discount_active);
const soldOut = computed(() => props.product.stock <= 0);
const lowStock = computed(
    () => props.product.stock > 0 && props.product.stock <= 5,
);

const processing = ref(false);
const justAdded = ref(false);
let addedTimer: ReturnType<typeof setTimeout> | undefined;

function addToCart(): void {
    if (processing.value) {
        return;
    }

    // Guests must sign in first; remember which product they wanted so it can
    // be added to their cart automatically once they authenticate.
    if (!props.canBuy) {
        router.get(login.url(), { add: props.product.id });

        return;
    }

    router.post(
        addToCartRoute.url(props.product.id),
        { quantity: 1 },
        {
            preserveScroll: true,
            onStart: () => {
                processing.value = true;
            },
            onSuccess: () => {
                justAdded.value = true;
                clearTimeout(addedTimer);
                addedTimer = setTimeout(() => {
                    justAdded.value = false;
                }, 1600);
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <div
        class="group relative flex flex-col overflow-hidden rounded-2xl border bg-card transition-all duration-300 hover:-translate-y-1 hover:border-foreground/25 hover:shadow-2xl"
    >
        <Link
            :href="productShow(product.slug)"
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

            <!-- Badges -->
            <div class="absolute inset-x-0 top-3 flex items-start justify-between px-3">
                <span
                    v-if="hasDiscount"
                    class="rounded-full bg-primary px-2.5 py-1 text-[11px] font-bold tracking-wide text-primary-foreground shadow-md"
                >
                    -{{ product.effective_discount_percent }}%
                </span>
                <span
                    v-if="lowStock"
                    class="ml-auto rounded-full bg-background/85 px-2.5 py-1 text-[10px] font-semibold tracking-wide text-foreground uppercase shadow-sm backdrop-blur"
                >
                    Only {{ product.stock }} left
                </span>
            </div>

            <span
                v-if="soldOut"
                class="absolute inset-0 flex items-center justify-center bg-background/60 text-xs font-semibold tracking-[0.22em] text-foreground uppercase backdrop-blur-[1px]"
            >
                Sold out
            </span>
        </Link>

        <div class="flex flex-1 flex-col p-4">
            <p
                v-if="product.brand"
                class="mb-1 text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
            >
                {{ product.brand.name }}
            </p>
            <Link
                :href="productShow(product.slug)"
                class="font-display line-clamp-2 text-lg leading-snug font-medium transition-colors group-hover:text-foreground"
            >
                {{ product.name }}
            </Link>

            <div class="mt-1.5 flex items-center gap-1.5 text-xs text-muted-foreground">
                <template v-if="product.reviews_count">
                    <StarRating :rating="Number(product.rating)" :size="13" />
                    <span>({{ product.reviews_count }})</span>
                </template>
                <span v-else>No reviews yet</span>
            </div>

            <div class="mt-4 flex items-end justify-between gap-2">
                <div class="flex flex-col leading-none">
                    <span
                        v-if="hasDiscount"
                        class="mb-1 text-xs text-muted-foreground line-through"
                    >
                        {{ formatMoney(product.price) }}
                    </span>
                    <span class="font-display text-xl font-semibold">
                        {{ formatMoney(product.final_price) }}
                    </span>
                </div>

                <button
                    v-if="!soldOut"
                    type="button"
                    :aria-label="justAdded ? 'Added to cart' : 'Add to cart'"
                    :disabled="processing"
                    :class="[
                        'flex size-11 shrink-0 items-center justify-center rounded-full shadow-sm transition-all hover:scale-105 active:scale-95 disabled:cursor-wait',
                        justAdded
                            ? 'bg-emerald-600 text-white'
                            : 'bg-primary text-primary-foreground',
                    ]"
                    @click="addToCart"
                >
                    <Spinner v-if="processing" class="size-4" />
                    <Check v-else-if="justAdded" :size="18" />
                    <ShoppingCart v-else :size="17" />
                </button>
            </div>
        </div>
    </div>
</template>
