<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Package } from '@lucide/vue';
import Pagination from '@/components/shop/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Order, Paginated } from '@/types/shop';

defineProps<{
    orders: Paginated<Order>;
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
    <Head title="My orders" />
    <ShopLayout>
        <div class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6">
            <h1 class="mb-6 font-display text-3xl font-semibold">My orders</h1>

            <div v-if="orders.data.length" class="space-y-3">
                <Link
                    v-for="order in orders.data"
                    :key="order.id"
                    :href="`/orders/${order.id}`"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-card p-4 transition-colors hover:bg-muted/40"
                >
                    <div>
                        <p class="font-medium">{{ order.order_number }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{
                                new Date(order.created_at).toLocaleDateString()
                            }}
                            · {{ order.items_count }} items
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Badge
                            :class="
                                cn(
                                    'border-transparent capitalize',
                                    statusTone[order.status],
                                )
                            "
                            >{{ order.status }}</Badge
                        >
                        <span class="font-semibold">{{
                            formatMoney(order.total)
                        }}</span>
                    </div>
                </Link>
                <div class="mt-6"><Pagination :links="orders.links" /></div>
            </div>

            <div
                v-else
                class="flex flex-col items-center gap-4 rounded-xl border border-dashed p-16 text-center"
            >
                <Package class="size-12 text-muted-foreground/50" />
                <p class="font-medium">No orders yet</p>
                <Button as-child
                    ><Link href="/products">Start shopping</Link></Button
                >
            </div>
        </div>
    </ShopLayout>
</template>
