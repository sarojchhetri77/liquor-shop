<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Banknote, Receipt, ShoppingBag, Users } from '@lucide/vue';
import { computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';

type RecentOrder = {
    id: number;
    order_number: string;
    customer_name: string;
    total: string;
    status: string;
    created_at: string | null;
};
type SeriesPoint = { label: string; short: string; revenue: number; orders: number };
type StatusRow = { status: string; count: number };
type CategoryRow = { name: string; revenue: number; qty: number };

const props = defineProps<{
    stats: {
        products: number;
        orders: number;
        pendingOrders: number;
        customers: number;
        revenue: number;
        avgOrderValue: number;
    };
    range: string;
    ranges: { value: string; label: string }[];
    chartLabel: string;
    revenueSeries: SeriesPoint[];
    statusBreakdown: StatusRow[];
    topCategories: CategoryRow[];
    recentOrders: RecentOrder[];
}>();

function setRange(value: string): void {
    router.get(
        '/admin',
        { range: value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const cards = computed(() => [
    { label: 'Total revenue', value: formatMoney(props.stats.revenue), icon: Banknote, tone: 'bg-emerald-500/10 text-emerald-600' },
    { label: 'Orders', value: props.stats.orders, icon: ShoppingBag, tone: 'bg-primary/10 text-primary', sub: `${props.stats.pendingOrders} pending` },
    { label: 'Avg. order value', value: formatMoney(props.stats.avgOrderValue), icon: Receipt, tone: 'bg-amber-500/10 text-amber-600' },
    { label: 'Customers', value: props.stats.customers, icon: Users, tone: 'bg-blue-500/10 text-blue-600' },
]);

// --- Revenue area chart geometry ---
const W = 320;
const H = 120;
const padTop = 12;
const padBottom = 10;

const maxRevenue = computed(() => Math.max(1, ...props.revenueSeries.map((p) => p.revenue)));

const points = computed(() =>
    props.revenueSeries.map((p, i) => {
        const n = props.revenueSeries.length;
        const x = n > 1 ? (i / (n - 1)) * W : 0;
        const y = H - padBottom - (p.revenue / maxRevenue.value) * (H - padTop - padBottom);

        return { x, y, ...p };
    }),
);

const linePath = computed(() =>
    points.value.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(1)} ${p.y.toFixed(1)}`).join(' '),
);
const areaPath = computed(() => `${linePath.value} L ${W} ${H} L 0 ${H} Z`);

const totalRevenue14 = computed(() => props.revenueSeries.reduce((s, p) => s + p.revenue, 0));
const totalOrders14 = computed(() => props.revenueSeries.reduce((s, p) => s + p.orders, 0));

const gridLines = [0.25, 0.5, 0.75].map((f) => padTop + f * (H - padTop - padBottom));

// --- Status + category bars ---
const statusColor: Record<string, string> = {
    pending: 'bg-amber-500',
    processing: 'bg-blue-500',
    shipped: 'bg-violet-500',
    delivered: 'bg-emerald-500',
    cancelled: 'bg-red-500',
};
const maxStatus = computed(() => Math.max(1, ...props.statusBreakdown.map((s) => s.count)));
const maxCategory = computed(() => Math.max(1, ...props.topCategories.map((c) => c.revenue)));

const statusTone: Record<string, string> = {
    pending: 'bg-amber-500/15 text-amber-700',
    processing: 'bg-blue-500/15 text-blue-700',
    shipped: 'bg-violet-500/15 text-violet-700',
    delivered: 'bg-emerald-500/15 text-emerald-700',
    cancelled: 'bg-red-500/15 text-red-700',
};
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout title="Dashboard">
        <!-- Date range filter -->
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted-foreground">
                Showing <span class="font-medium text-foreground">{{ chartLabel.toLowerCase() }}</span>
            </p>
            <div class="flex flex-wrap gap-1 rounded-full border bg-card p-1">
                <button
                    v-for="r in ranges"
                    :key="r.value"
                    :class="cn('rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors', range === r.value ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground')"
                    @click="setRange(r.value)"
                >
                    {{ r.label }}
                </button>
            </div>
        </div>

        <!-- KPI cards -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="card in cards"
                :key="card.label"
                class="rounded-2xl border bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">{{ card.label }}</span>
                    <span :class="cn('flex size-10 items-center justify-center rounded-xl', card.tone)">
                        <component :is="card.icon" class="size-5" />
                    </span>
                </div>
                <p class="mt-4 font-display text-3xl font-semibold">{{ card.value }}</p>
                <p v-if="card.sub" class="mt-1 text-xs text-muted-foreground">{{ card.sub }}</p>
            </div>
        </div>

        <!-- Revenue chart + status -->
        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border bg-card p-5 shadow-sm lg:col-span-2">
                <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="font-display text-lg font-semibold">Revenue</h2>
                        <p class="text-xs text-muted-foreground">{{ chartLabel }}</p>
                    </div>
                    <div class="flex gap-6 text-right">
                        <div>
                            <p class="font-display text-xl font-semibold">{{ formatMoney(totalRevenue14) }}</p>
                            <p class="text-[11px] tracking-wide text-muted-foreground uppercase">Revenue</p>
                        </div>
                        <div>
                            <p class="font-display text-xl font-semibold">{{ totalOrders14 }}</p>
                            <p class="text-[11px] tracking-wide text-muted-foreground uppercase">Orders</p>
                        </div>
                    </div>
                </div>

                <svg :viewBox="`0 0 ${W} ${H}`" preserveAspectRatio="none" class="h-44 w-full overflow-visible">
                    <defs>
                        <linearGradient id="revFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="var(--primary)" stop-opacity="0.28" />
                            <stop offset="100%" stop-color="var(--primary)" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    <line
                        v-for="(gy, i) in gridLines"
                        :key="i"
                        x1="0"
                        :y1="gy"
                        :x2="W"
                        :y2="gy"
                        stroke="var(--border)"
                        stroke-width="1"
                        vector-effect="non-scaling-stroke"
                    />
                    <path :d="areaPath" fill="url(#revFill)" />
                    <path
                        :d="linePath"
                        fill="none"
                        stroke="var(--primary)"
                        stroke-width="2"
                        stroke-linejoin="round"
                        stroke-linecap="round"
                        vector-effect="non-scaling-stroke"
                    />
                    <circle
                        v-for="(p, i) in points"
                        :key="i"
                        :cx="p.x"
                        :cy="p.y"
                        r="2.5"
                        fill="var(--primary)"
                        vector-effect="non-scaling-stroke"
                    />
                </svg>
                <div class="mt-2 flex justify-between text-[10px] text-muted-foreground">
                    <span>{{ revenueSeries[0]?.label }}</span>
                    <span>{{ revenueSeries[Math.floor(revenueSeries.length / 2)]?.label }}</span>
                    <span>{{ revenueSeries[revenueSeries.length - 1]?.label }}</span>
                </div>
            </div>

            <!-- Order status breakdown -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <h2 class="mb-5 font-display text-lg font-semibold">Orders by status</h2>
                <div class="space-y-4">
                    <div v-for="row in statusBreakdown" :key="row.status">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="capitalize">{{ row.status }}</span>
                            <span class="font-medium">{{ row.count }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                :class="cn('h-full rounded-full transition-all', statusColor[row.status])"
                                :style="{ width: `${(row.count / maxStatus) * 100}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top categories + recent orders -->
        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <h2 class="mb-5 font-display text-lg font-semibold">Top categories</h2>
                <div v-if="topCategories.length" class="space-y-4">
                    <div v-for="cat in topCategories" :key="cat.name">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="font-medium">{{ cat.name }}</span>
                            <span class="text-muted-foreground">{{ formatMoney(cat.revenue) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-primary to-rose-500"
                                :style="{ width: `${(cat.revenue / maxCategory) * 100}%` }"
                            ></div>
                        </div>
                        <p class="mt-1 text-[11px] text-muted-foreground">{{ cat.qty }} units sold</p>
                    </div>
                </div>
                <p v-else class="py-8 text-center text-sm text-muted-foreground">No sales data yet.</p>
            </div>

            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-display text-lg font-semibold">Recent orders</h2>
                    <Link href="/admin/orders" class="flex items-center gap-1 text-xs font-semibold tracking-[0.1em] text-primary uppercase hover:underline">
                        View all <ArrowRight class="size-3.5" />
                    </Link>
                </div>

                <div v-if="recentOrders.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-[11px] font-semibold tracking-[0.1em] text-muted-foreground uppercase">
                                <th class="px-5 py-3">Order</th>
                                <th class="px-5 py-3">Customer</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="order in recentOrders"
                                :key="order.id"
                                class="cursor-pointer transition-colors hover:bg-muted/40"
                                @click="$inertia.visit(`/admin/orders/${order.id}`)"
                            >
                                <td class="px-5 py-3 font-medium">{{ order.order_number }}</td>
                                <td class="px-5 py-3">
                                    <p>{{ order.customer_name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ order.created_at }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <span :class="cn('rounded-full px-2.5 py-1 text-xs font-medium capitalize', statusTone[order.status])">
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right font-display font-semibold">{{ formatMoney(order.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="flex flex-col items-center gap-2 px-5 py-14 text-center">
                    <ShoppingBag class="size-8 text-muted-foreground/40" />
                    <p class="text-sm text-muted-foreground">No orders yet.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
