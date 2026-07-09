<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, Eye, MoreHorizontal } from '@lucide/vue';
import Pagination from '@/components/shop/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
    pending: 'bg-amber-500/15 text-amber-700',
    processing: 'bg-blue-500/15 text-blue-700',
    shipped: 'bg-violet-500/15 text-violet-700',
    delivered: 'bg-emerald-500/15 text-emerald-700',
    cancelled: 'bg-red-500/15 text-red-700',
};

function filterStatus(status: string): void {
    router.get(
        '/admin/orders',
        { status: status || undefined },
        { preserveState: true, replace: true },
    );
}

function setStatus(order: Order, status: string): void {
    if (status === order.status) {
        return;
    }

    router.patch(
        `/admin/orders/${order.id}/status`,
        { status },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Orders" />
    <AdminLayout title="Orders">
        <div class="flex flex-wrap gap-2">
            <button
                :class="cn('rounded-full px-4 py-1.5 text-sm font-medium transition-colors', !filters.status ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:text-foreground')"
                @click="filterStatus('')"
            >
                All
            </button>
            <button
                v-for="status in statuses"
                :key="status"
                :class="cn('rounded-full px-4 py-1.5 text-sm font-medium capitalize transition-colors', filters.status === status ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:text-foreground')"
                @click="filterStatus(status)"
            >
                {{ status }}
            </button>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-[11px] font-semibold tracking-[0.1em] text-muted-foreground uppercase">
                            <th class="px-5 py-3.5">Order</th>
                            <th class="px-5 py-3.5">Customer</th>
                            <th class="px-5 py-3.5">Total</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Placed</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="order in orders.data"
                            :key="order.id"
                            class="cursor-pointer transition-colors hover:bg-muted/40"
                            @click="router.visit(`/admin/orders/${order.id}`)"
                        >
                            <td class="px-5 py-3.5 font-medium">{{ order.order_number }}</td>
                            <td class="px-5 py-3.5">
                                <p>{{ order.customer_name }}</p>
                                <p class="text-xs text-muted-foreground">{{ order.user?.email }}</p>
                            </td>
                            <td class="px-5 py-3.5 font-display font-semibold">{{ formatMoney(order.total) }}</td>
                            <td class="px-5 py-3.5">
                                <Badge :class="cn('border-transparent capitalize', statusTone[order.status])">{{ order.status }}</Badge>
                            </td>
                            <td class="px-5 py-3.5 text-muted-foreground">{{ new Date(order.created_at).toLocaleDateString() }}</td>
                            <td class="px-5 py-3.5 text-right" @click.stop>
                                <DropdownMenu>
                                    <DropdownMenuTrigger
                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-muted hover:text-foreground"
                                        aria-label="Order actions"
                                    >
                                        <MoreHorizontal class="size-4" />
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-48">
                                        <DropdownMenuItem as-child>
                                            <Link :href="`/admin/orders/${order.id}`" class="flex w-full items-center gap-2">
                                                <Eye class="size-4" /> View details
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuLabel class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
                                            Set status
                                        </DropdownMenuLabel>
                                        <DropdownMenuItem
                                            v-for="s in statuses"
                                            :key="s"
                                            class="gap-2 capitalize"
                                            @click="setStatus(order, s)"
                                        >
                                            <Check :class="cn('size-4 text-primary', s === order.status ? 'opacity-100' : 'opacity-0')" />
                                            {{ s }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                        <tr v-if="!orders.data.length">
                            <td colspan="6" class="px-5 py-10 text-center text-muted-foreground">No orders found.</td>
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
