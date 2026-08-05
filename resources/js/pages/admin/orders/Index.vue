<script setup lang="ts">
import { Head, Link, router, usePoll } from '@inertiajs/vue3';
import { Check, Eye, MoreHorizontal, Search, X } from '@lucide/vue';
import { reactive } from 'vue';
import Pagination from '@/components/shop/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

const props = defineProps<{
    orders: Paginated<Order>;
    filters: { status: string; search: string; date_from: string; date_to: string };
    statuses: string[];
}>();

const filters = reactive({
    status: props.filters.status ?? '',
    search: props.filters.search ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

// Auto-refresh the orders table every 10s so new orders appear without a manual
// reload. Only the `orders` prop is re-fetched, and the current filters/URL are
// preserved, so this is a lightweight background request.
usePoll(10000, { only: ['orders'] });

const statusTone: Record<string, string> = {
    pending: 'bg-amber-500/15 text-amber-700',
    processing: 'bg-blue-500/15 text-blue-700',
    shipped: 'bg-violet-500/15 text-violet-700',
    delivered: 'bg-emerald-500/15 text-emerald-700',
    cancelled: 'bg-red-500/15 text-red-700',
};

function applyFilters(): void {
    router.get(
        '/admin/orders',
        {
            status: filters.status || undefined,
            search: filters.search || undefined,
            date_from: filters.date_from || undefined,
            date_to: filters.date_to || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function filterStatus(status: string): void {
    filters.status = status;
    applyFilters();
}

function clearFilters(): void {
    filters.status = '';
    filters.search = '';
    filters.date_from = '';
    filters.date_to = '';
    applyFilters();
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
        <!-- Search + date filters -->
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
            <div class="relative flex-1 sm:max-w-xs">
                <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Order #, customer, contact, email…"
                    class="h-11 w-full rounded-lg border bg-card pr-3 pl-9 text-sm outline-none transition-colors focus:border-primary"
                    @keyup.enter="applyFilters"
                />
            </div>
            <label class="flex flex-col gap-1 text-xs text-muted-foreground">
                From
                <input
                    v-model="filters.date_from"
                    type="date"
                    class="h-11 rounded-lg border bg-card px-3 text-sm outline-none transition-colors focus:border-primary"
                    @change="applyFilters"
                />
            </label>
            <label class="flex flex-col gap-1 text-xs text-muted-foreground">
                To
                <input
                    v-model="filters.date_to"
                    type="date"
                    class="h-11 rounded-lg border bg-card px-3 text-sm outline-none transition-colors focus:border-primary"
                    @change="applyFilters"
                />
            </label>
            <Button variant="secondary" class="h-11" @click="applyFilters">Search</Button>
            <Button
                v-if="filters.search || filters.date_from || filters.date_to || filters.status"
                variant="ghost"
                class="h-11 gap-1.5"
                @click="clearFilters"
            >
                <X class="size-4" /> Clear
            </Button>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
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
