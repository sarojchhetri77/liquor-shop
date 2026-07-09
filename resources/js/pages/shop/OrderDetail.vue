<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2 } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Order } from '@/types/shop';

defineProps<{
    order: Order;
}>();

const statusTone: Record<string, string> = {
    pending: 'bg-amber-500/15 text-amber-600',
    processing: 'bg-blue-500/15 text-blue-600',
    shipped: 'bg-violet-500/15 text-violet-600',
    delivered: 'bg-emerald-500/15 text-emerald-600',
    cancelled: 'bg-red-500/15 text-red-600',
};
</script>

<template>
    <Head :title="order.order_number" />
    <ShopLayout>
        <div class="mx-auto w-full max-w-3xl px-4 py-8 sm:px-6">
            <Link
                href="/orders"
                class="mb-4 inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-4" /> Back to orders
            </Link>

            <div
                class="mb-4 flex items-center gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/5 p-4"
            >
                <CheckCircle2 class="size-6 text-emerald-600" />
                <div>
                    <p class="font-medium">Order confirmed</p>
                    <p class="text-sm text-muted-foreground">
                        We'll deliver soon. Pay cash on delivery.
                    </p>
                </div>
            </div>

            <div class="rounded-xl border bg-card">
                <div class="flex items-center justify-between border-b p-5">
                    <div>
                        <p class="font-semibold">{{ order.order_number }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ new Date(order.created_at).toLocaleString() }}
                        </p>
                    </div>
                    <Badge
                        :class="
                            cn(
                                'border-transparent capitalize',
                                statusTone[order.status],
                            )
                        "
                        >{{ order.status }}</Badge
                    >
                </div>

                <div class="divide-y">
                    <div
                        v-for="item in order.items"
                        :key="item.id"
                        class="flex items-center gap-3 p-4"
                    >
                        <img
                            :src="
                                item.product?.images?.[0]?.url ??
                                'https://placehold.co/80'
                            "
                            class="size-14 rounded-md border object-cover"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">
                                {{ item.product_name }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ formatMoney(item.unit_price) }} ×
                                {{ item.quantity }}
                            </p>
                        </div>
                        <span class="font-semibold">{{
                            formatMoney(item.line_total)
                        }}</span>
                    </div>
                </div>

                <div class="space-y-1 border-t p-5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Subtotal</span>
                        <span>{{ formatMoney(order.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-semibold">
                        <span>Total</span>
                        <span>{{ formatMoney(order.total) }}</span>
                    </div>
                </div>

                <div class="border-t p-5 text-sm">
                    <h2 class="mb-2 font-semibold">Delivery to</h2>
                    <p>{{ order.customer_name }} · {{ order.contact }}</p>
                    <p class="whitespace-pre-line text-muted-foreground">
                        {{ order.shipping_address }}
                    </p>
                    <p v-if="order.note" class="mt-1 text-muted-foreground">
                        Note: {{ order.note }}
                    </p>
                </div>
            </div>
        </div>
    </ShopLayout>
</template>
