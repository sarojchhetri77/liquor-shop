<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Minus, Plus, ShoppingBag, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { formatMoney } from '@/lib/format';
import { destroy as removeItem, update as updateItem } from '@/routes/shop/cart';
import { create as checkout } from '@/routes/shop/checkout';
import { index as products, show as productShow } from '@/routes/shop/products';

type CartItem = {
    id: number;
    quantity: number;
    product: {
        id: number;
        name: string;
        slug: string;
        price: string;
        final_price: number;
        discount_percent: number;
        image: string | null;
        stock: number;
    };
};

defineProps<{
    items: CartItem[];
    totals: { subtotal: number; total: number; count: number };
}>();

function updateQuantity(item: CartItem, quantity: number): void {
    if (quantity < 1) {
        return;
    }

    router.patch(updateItem.url(item.id), { quantity }, { preserveScroll: true });
}

function remove(item: CartItem): void {
    router.delete(removeItem.url(item.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Your cart" />
    <ShopLayout>
        <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6">
            <h1 class="mb-6 font-display text-3xl font-semibold">Your cart</h1>

            <div v-if="items.length" class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-3 lg:col-span-2">
                    <div
                        v-for="item in items"
                        :key="item.id"
                        class="flex gap-4 rounded-xl border bg-card p-4"
                    >
                        <Link
                            :href="productShow(item.product.slug)"
                            class="size-24 shrink-0 overflow-hidden rounded-lg border bg-muted"
                        >
                            <img
                                :src="
                                    item.product.image ??
                                    'https://placehold.co/120'
                                "
                                class="h-full w-full object-cover"
                            />
                        </Link>
                        <div class="flex flex-1 flex-col">
                            <div class="flex items-start justify-between gap-2">
                                <Link
                                    :href="productShow(item.product.slug)"
                                    class="font-medium hover:underline"
                                    >{{ item.product.name }}</Link
                                >
                                <button
                                    class="text-muted-foreground hover:text-red-600"
                                    @click="remove(item)"
                                >
                                    <Trash2 class="size-4" />
                                </button>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{ formatMoney(item.product.final_price) }} each
                            </p>
                            <div
                                class="mt-auto flex items-center justify-between pt-2"
                            >
                                <div
                                    class="flex items-center rounded-lg border"
                                >
                                    <button
                                        class="flex size-8 items-center justify-center text-muted-foreground hover:text-foreground"
                                        @click="
                                            updateQuantity(
                                                item,
                                                item.quantity - 1,
                                            )
                                        "
                                    >
                                        <Minus class="size-3.5" />
                                    </button>
                                    <span
                                        class="w-8 text-center text-sm font-medium"
                                        >{{ item.quantity }}</span
                                    >
                                    <button
                                        class="flex size-8 items-center justify-center text-muted-foreground hover:text-foreground"
                                        @click="
                                            updateQuantity(
                                                item,
                                                item.quantity + 1,
                                            )
                                        "
                                    >
                                        <Plus class="size-3.5" />
                                    </button>
                                </div>
                                <span class="font-semibold">{{
                                    formatMoney(
                                        item.product.final_price *
                                            item.quantity,
                                    )
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="h-fit rounded-xl border bg-card p-5">
                    <h2 class="mb-4 font-semibold">Order summary</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">
                                Subtotal ({{ totals.count }} items)
                            </dt>
                            <dd>{{ formatMoney(totals.subtotal) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Delivery</dt>
                            <dd class="text-emerald-600">Free</dd>
                        </div>
                        <div
                            class="flex justify-between border-t pt-2 text-base font-semibold"
                        >
                            <dt>Total</dt>
                            <dd>{{ formatMoney(totals.total) }}</dd>
                        </div>
                    </dl>
                    <Button as-child class="mt-4 w-full">
                        <Link :href="checkout()">Proceed to checkout</Link>
                    </Button>
                    <Link
                        :href="products()"
                        class="mt-2 block text-center text-sm text-muted-foreground hover:text-foreground"
                        >Continue shopping</Link
                    >
                </div>
            </div>

            <div
                v-else
                class="flex flex-col items-center gap-4 rounded-xl border border-dashed p-16 text-center"
            >
                <ShoppingBag class="size-12 text-muted-foreground/50" />
                <div>
                    <p class="font-medium">Your cart is empty</p>
                    <p class="text-sm text-muted-foreground">
                        Browse our products and add something you love.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="products()">Start shopping</Link>
                </Button>
            </div>
        </div>
    </ShopLayout>
</template>
