<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    ChevronRight,
    Clock,
    KeyRound,
    Mail,
    Package,
    Pencil,
    Phone,
    ShoppingBag,
    User as UserIcon,
    Wallet,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { Order } from '@/types/shop';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const props = defineProps<{
    profile: {
        name: string;
        email: string;
        contact: string | null;
        dob: string | null;
        member_since: string | null;
    };
    stats: {
        orders: number;
        spent: number;
        pending: number;
    };
    recentOrders: Order[];
}>();

const firstName = props.profile.name.split(' ')[0];

const statusTone: Record<string, string> = {
    pending: 'bg-amber-500/15 text-amber-500',
    processing: 'bg-blue-500/15 text-blue-400',
    shipped: 'bg-violet-500/15 text-violet-400',
    delivered: 'bg-emerald-500/15 text-emerald-400',
    cancelled: 'bg-red-500/15 text-red-400',
};

const statCards = [
    { key: 'orders', label: 'Total orders', icon: Package, value: () => String(props.stats.orders) },
    { key: 'spent', label: 'Total spent', icon: Wallet, value: () => formatMoney(props.stats.spent) },
    { key: 'pending', label: 'Pending', icon: Clock, value: () => String(props.stats.pending) },
];

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleDateString() : '—';
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="mx-auto w-full max-w-5xl p-4 sm:p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="font-display text-3xl font-semibold">
                Welcome back, {{ firstName }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Here's an overview of your orders and account details.
            </p>
        </div>

        <!-- Stats -->
        <div class="mb-8 grid gap-4 sm:grid-cols-3">
            <div
                v-for="stat in statCards"
                :key="stat.key"
                class="rounded-xl border bg-card p-5"
            >
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ stat.label }}</span>
                    <span
                        class="flex size-9 items-center justify-center rounded-full bg-primary/10 text-primary"
                    >
                        <component :is="stat.icon" class="size-4.5" />
                    </span>
                </div>
                <p class="mt-3 font-display text-2xl font-semibold">
                    {{ stat.value() }}
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Recent orders -->
            <section class="lg:col-span-2">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-display text-lg font-semibold">Recent orders</h2>
                    <Link
                        href="/orders"
                        class="flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                    >
                        View all <ChevronRight class="size-4" />
                    </Link>
                </div>

                <div v-if="recentOrders.length" class="space-y-3">
                    <Link
                        v-for="order in recentOrders"
                        :key="order.id"
                        :href="`/orders/${order.id}`"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-card p-4 transition-colors hover:bg-muted/40"
                    >
                        <div>
                            <p class="font-medium">{{ order.order_number }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ formatDate(order.created_at) }}
                                · {{ order.items_count }} items
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <Badge
                                :class="cn('border-transparent capitalize', statusTone[order.status])"
                            >
                                {{ order.status }}
                            </Badge>
                            <span class="font-semibold">{{ formatMoney(order.total) }}</span>
                        </div>
                    </Link>
                </div>

                <div
                    v-else
                    class="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center"
                >
                    <Package class="size-10 text-muted-foreground/50" />
                    <p class="font-medium">No orders yet</p>
                    <Button as-child>
                        <Link href="/products">Start shopping</Link>
                    </Button>
                </div>
            </section>

            <!-- Account details + actions -->
            <aside class="space-y-6">
                <div class="rounded-xl border bg-card p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="font-display text-lg font-semibold">Details</h2>
                        <Link
                            href="/settings/profile"
                            class="flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                        >
                            <Pencil class="size-3.5" /> Edit
                        </Link>
                    </div>
                    <dl class="space-y-3.5 text-sm">
                        <div class="flex items-center gap-3">
                            <UserIcon class="size-4 shrink-0 text-muted-foreground" />
                            <span class="truncate">{{ profile.name }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <Mail class="size-4 shrink-0 text-muted-foreground" />
                            <span class="truncate">{{ profile.email }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <Phone class="size-4 shrink-0 text-muted-foreground" />
                            <span>{{ profile.contact || '—' }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <CalendarDays class="size-4 shrink-0 text-muted-foreground" />
                            <span>Born {{ formatDate(profile.dob) }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <ShoppingBag class="size-4 shrink-0 text-muted-foreground" />
                            <span>Member since {{ formatDate(profile.member_since) }}</span>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border bg-card p-5">
                    <h2 class="mb-4 font-display text-lg font-semibold">Quick actions</h2>
                    <div class="grid gap-2">
                        <Button variant="outline" class="justify-start" as-child>
                            <Link href="/settings/profile">
                                <UserIcon class="size-4" /> Edit profile
                            </Link>
                        </Button>
                        <Button variant="outline" class="justify-start" as-child>
                            <Link href="/settings/security">
                                <KeyRound class="size-4" /> Change password
                            </Link>
                        </Button>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>
