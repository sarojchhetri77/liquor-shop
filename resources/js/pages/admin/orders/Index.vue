<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Pagination from '@/components/shop/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Order, Paginated } from '@/types/shop';

defineProps<{
    orders: Paginated<Order>;
    filters: { status: string };
    statuses: string[];
}>();

const statusTone: Record<string, string> = {
    pending: 'bg-amber-500/15 text-amber-600',
    processing: 'bg-blue-500/15 text-blue-600',
    shipped: 'bg-violet-500/15 text-violet-600',
    delivered: 'bg-emerald-500/15 text-emerald-600',
    cancelled: 'bg-red-500/15 text-red-600',
};

function filterStatus(status: string): void {
    router.get(
        '/admin/orders',
        { status: status || undefined },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Orders" />
    <AdminLayout title="Orders">
        <div class="flex flex-wrap gap-2">
            <button
                :class="
                    cn(
                        'rounded-full px-4 py-1.5 text-sm font-medium transition-colors',
                        !filters.status
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted text-muted-foreground hover:text-foreground',
                    )
                "
                @click="filterStatus('')"
            >
                All
            </button>
            <button
                v-for="status in statuses"
                :key="status"
                :class="
                    cn(
                        'rounded-full px-4 py-1.5 text-sm font-medium capitalize transition-colors',
                        filters.status === status
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted text-muted-foreground hover:text-foreground',
                    )
                "
                @click="filterStatus(status)"
            >
                {{ status }}
            </button>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b bg-muted/40 text-left text-muted-foreground"
                    >
                        <tr>
                            <th class="p-3 font-medium">Order</th>
                            <th class="p-3 font-medium">Customer</th>
                            <th class="p-3 font-medium">Total</th>
                            <th class="p-3 font-medium">Status</th>
                            <th class="p-3 font-medium">Placed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="order in orders.data"
                            :key="order.id"
                            class="cursor-pointer hover:bg-muted/30"
                            @click="router.visit(`/admin/orders/${order.id}`)"
                        >
                            <td class="p-3 font-medium">
                                {{ order.order_number }}
                            </td>
                            <td class="p-3">
                                <p>{{ order.customer_name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ order.user?.email }}
                                </p>
                            </td>
                            <td class="p-3 font-semibold">
                                {{ formatMoney(order.total) }}
                            </td>
                            <td class="p-3">
                                <Badge
                                    :class="
                                        cn(
                                            'border-transparent capitalize',
                                            statusTone[order.status],
                                        )
                                    "
                                    >{{ order.status }}</Badge
                                >
                            </td>
                            <td class="p-3 text-muted-foreground">
                                {{
                                    new Date(
                                        order.created_at,
                                    ).toLocaleDateString()
                                }}
                            </td>
                        </tr>
                        <tr v-if="!orders.data.length">
                            <td
                                colspan="5"
                                class="p-8 text-center text-muted-foreground"
                            >
                                No orders found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <Pagination :links="orders.links" />
        </div>
    </AdminLayout>
</template>
