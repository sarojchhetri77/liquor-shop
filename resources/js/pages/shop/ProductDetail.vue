<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Minus, Plus, ShoppingCart } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import ProductCard from '@/components/shop/ProductCard.vue';
import StarRating from '@/components/shop/StarRating.vue';
import { Button } from '@/components/ui/button';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { formatMoney } from '@/lib/format';
import { login } from '@/routes';
import { store as addToCartRoute } from '@/routes/shop/cart';
import { index as products } from '@/routes/shop/products';
import { store as storeReview } from '@/routes/shop/reviews';
import type { Product, Review } from '@/types/shop';

const props = defineProps<{
    product: Product;
    userReview: Review | null;
    related: Product[];
}>();

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth.user);

const activeImage = ref(
    props.product.images?.[0]?.url ?? 'https://placehold.co/700',
);
const quantity = ref(1);
const hasDiscount = computed(() => props.product.discount_percent > 0);

function addToCart(): void {
    if (!isAuthenticated.value) {
        router.visit(login.url());

        return;
    }

    router.post(
        addToCartRoute.url(props.product.id),
        { quantity: quantity.value },
        { preserveScroll: true },
    );
}

const reviewForm = useForm({
    rating: props.userReview?.rating ?? 0,
    comment: props.userReview?.comment ?? '',
});

function submitReview(): void {
    reviewForm.post(storeReview.url(props.product.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="product.name" />
    <ShopLayout>
        <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6">
            <nav
                class="mb-6 flex items-center gap-2 text-sm text-muted-foreground"
            >
                <Link :href="products()" class="hover:text-foreground">Shop</Link>
                <span>/</span>
                <Link
                    :href="products({ query: { category_id: product.category_id } })"
                    class="hover:text-foreground"
                    >{{ product.category?.name }}</Link
                >
            </nav>

            <div class="grid gap-8 lg:grid-cols-2">
                <!-- Gallery -->
                <div class="space-y-3">
                    <div
                        class="aspect-square overflow-hidden rounded-2xl border bg-muted"
                    >
                        <img
                            :src="activeImage"
                            :alt="product.name"
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <div
                        v-if="product.images && product.images.length > 1"
                        class="flex gap-2"
                    >
                        <button
                            v-for="image in product.images"
                            :key="image.id"
                            class="size-20 overflow-hidden rounded-lg border-2 transition-colors"
                            :class="
                                activeImage === image.url
                                    ? 'border-primary'
                                    : 'border-transparent'
                            "
                            @click="activeImage = image.url"
                        >
                            <img
                                :src="image.url"
                                class="h-full w-full object-cover"
                            />
                        </button>
                    </div>
                </div>

                <!-- Info -->
                <div class="space-y-5">
                    <div>
                        <p
                            v-if="product.brand"
                            class="text-xs font-semibold tracking-[0.16em] text-primary/70 uppercase"
                        >
                            {{ product.brand.name }}
                        </p>
                        <h1 class="mt-2 font-display text-4xl leading-tight font-semibold">
                            {{ product.name }}
                        </h1>
                        <div class="mt-2 flex items-center gap-2">
                            <StarRating
                                :rating="Number(product.rating)"
                                :size="18"
                            />
                            <span class="text-sm text-muted-foreground">
                                {{ Number(product.rating).toFixed(1) }} ({{
                                    product.reviews_count
                                }}
                                reviews)
                            </span>
                        </div>
                    </div>

                    <div class="flex items-end gap-3">
                        <span class="font-display text-3xl font-semibold">{{
                            formatMoney(product.final_price)
                        }}</span>
                        <template v-if="hasDiscount">
                            <span
                                class="text-lg text-muted-foreground line-through"
                                >{{ formatMoney(product.price) }}</span
                            >
                            <span
                                class="rounded-full bg-red-500/15 px-2 py-0.5 text-sm font-medium text-red-600"
                            >
                                Save {{ product.discount_percent }}%
                            </span>
                        </template>
                    </div>

                    <p class="whitespace-pre-line text-muted-foreground">
                        {{ product.description }}
                    </p>

                    <div class="flex items-center gap-2 text-sm">
                        <span
                            :class="
                                product.stock > 0
                                    ? 'text-emerald-600'
                                    : 'text-red-600'
                            "
                        >
                            {{
                                product.stock > 0
                                    ? `In stock (${product.stock})`
                                    : 'Out of stock'
                            }}
                        </span>
                    </div>

                    <div
                        v-if="product.stock > 0"
                        class="flex flex-wrap items-center gap-3"
                    >
                        <div class="flex items-center rounded-lg border">
                            <button
                                class="flex size-10 items-center justify-center text-muted-foreground hover:text-foreground"
                                @click="quantity = Math.max(1, quantity - 1)"
                            >
                                <Minus class="size-4" />
                            </button>
                            <span class="w-10 text-center font-medium">{{
                                quantity
                            }}</span>
                            <button
                                class="flex size-10 items-center justify-center text-muted-foreground hover:text-foreground"
                                @click="
                                    quantity = Math.min(
                                        product.stock,
                                        quantity + 1,
                                    )
                                "
                            >
                                <Plus class="size-4" />
                            </button>
                        </div>
                        <Button
                            size="lg"
                            class="flex-1 gap-2 sm:flex-none"
                            @click="addToCart"
                        >
                            <ShoppingCart class="size-5" /> Add to cart
                        </Button>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        This product is currently unavailable.
                    </p>
                </div>
            </div>

            <!-- Reviews -->
            <div class="mt-12 grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <h2 class="mb-4 font-display text-2xl font-semibold">Customer reviews</h2>
                    <div
                        v-if="product.reviews && product.reviews.length"
                        class="space-y-4"
                    >
                        <div
                            v-for="review in product.reviews"
                            :key="review.id"
                            class="rounded-xl border bg-card p-4"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex size-8 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary"
                                    >
                                        {{
                                            review.user?.name?.charAt(0) ?? '?'
                                        }}
                                    </span>
                                    <span class="text-sm font-medium">{{
                                        review.user?.name ?? 'Customer'
                                    }}</span>
                                </div>
                                <StarRating
                                    :rating="review.rating"
                                    :size="14"
                                />
                            </div>
                            <p
                                v-if="review.comment"
                                class="mt-2 text-sm text-muted-foreground"
                            >
                                {{ review.comment }}
                            </p>
                        </div>
                    </div>
                    <p
                        v-else
                        class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
                    >
                        No reviews yet. Be the first to review this product!
                    </p>
                </div>

                <!-- Review form -->
                <div>
                    <h2 class="mb-4 font-display text-2xl font-semibold">
                        {{
                            userReview ? 'Update your review' : 'Write a review'
                        }}
                    </h2>
                    <div
                        v-if="isAuthenticated"
                        class="rounded-xl border bg-card p-5"
                    >
                        <form class="space-y-4" @submit.prevent="submitReview">
                            <div>
                                <p class="mb-1.5 text-sm font-medium">
                                    Your rating
                                </p>
                                <StarRating
                                    v-model="reviewForm.rating"
                                    :readonly="false"
                                    :size="28"
                                />
                                <InputError
                                    :message="reviewForm.errors.rating"
                                />
                            </div>
                            <div>
                                <p class="mb-1.5 text-sm font-medium">
                                    Your review
                                </p>
                                <textarea
                                    v-model="reviewForm.comment"
                                    rows="4"
                                    placeholder="Share your thoughts..."
                                    class="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:border-primary"
                                />
                                <InputError
                                    :message="reviewForm.errors.comment"
                                />
                            </div>
                            <Button
                                type="submit"
                                class="w-full"
                                :disabled="
                                    reviewForm.processing ||
                                    reviewForm.rating === 0
                                "
                            >
                                {{
                                    userReview
                                        ? 'Update review'
                                        : 'Submit review'
                                }}
                            </Button>
                        </form>
                    </div>
                    <div
                        v-else
                        class="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground"
                    >
                        <Link
                            :href="login()"
                            class="font-medium text-primary hover:underline"
                            >Log in</Link
                        >
                        to write a review.
                    </div>
                </div>
            </div>

            <!-- Related -->
            <div v-if="related.length" class="mt-12">
                <h2 class="mb-6 font-display text-2xl font-semibold">You may also like</h2>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <ProductCard
                        v-for="item in related"
                        :key="item.id"
                        :product="item"
                        :can-buy="isAuthenticated"
                    />
                </div>
            </div>
        </div>
    </ShopLayout>
</template>
