<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Banknote,
    Check,
    MapPin,
    Phone,
    StickyNote,
    User as UserIcon,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import BottleThumb from '@/components/shop/BottleThumb.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
import { index as adminOrders, status as orderStatus } from '@/routes/admin/orders';
import type { Order } from '@/types/shop';

const props = defineProps<{
    order: Order;
    statuses: string[];
}>();

const status = ref(props.order.status);

const flow = ['pending', 'processing', 'shipped', 'delivered'];
const isCancelled = computed(() => props.order.status === 'cancelled');
const currentIndex = computed(() => flow.indexOf(props.order.status));
const itemCount = computed(() =>
    (props.order.items ?? []).reduce((sum, i) => sum + i.quantity, 0),
);

const statusTone: Record<string, string> = {
    pending: 'bg-amber-500/15 text-amber-700',
    processing: 'bg-blue-500/15 text-blue-700',
    shipped: 'bg-violet-500/15 text-violet-700',
    delivered: 'bg-emerald-500/15 text-emerald-700',
    cancelled: 'bg-red-500/15 text-red-700',
};

const placedAt = computed(() =>
    props.order.created_at ? new Date(props.order.created_at).toLocaleString() : '',
);

function updateStatus(): void {
    router.patch(
        orderStatus.url(props.order.id),
        { status: status.value },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="order.order_number" />
    <AdminLayout :title="order.order_number">
        <Link
            :href="adminOrders()"
            class="mb-4 inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> Back to orders
        </Link>

        <!-- Header summary -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border bg-card p-5 shadow-sm">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="font-display text-2xl font-semibold">{{ order.order_number }}</h1>
                    <span :class="cn('rounded-full px-2.5 py-1 text-xs font-medium capitalize', statusTone[order.status])">
                        {{ order.status }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">Placed {{ placedAt }} · {{ itemCount }} items</p>
            </div>
            <div class="text-right">
                <p class="font-display text-2xl font-semibold">{{ formatMoney(order.total) }}</p>
                <p class="text-xs tracking-wide text-muted-foreground uppercase">Order total</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <!-- Progress timeline -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <h2 class="mb-5 font-display text-lg font-semibold">Progress</h2>
                    <div v-if="isCancelled" class="flex items-center gap-3 rounded-xl bg-red-500/10 px-4 py-3 text-sm font-medium text-red-700">
                        This order was cancelled.
                    </div>
                    <div v-else class="flex items-center">
                        <template v-for="(step, i) in flow" :key="step">
                            <div class="flex flex-col items-center gap-2">
                                <span
                                    :class="cn(
                                        'flex size-9 items-center justify-center rounded-full border-2 transition-colors',
                                        i <= currentIndex
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-muted-foreground/30 text-muted-foreground',
                                    )"
                                >
                                    <Check v-if="i < currentIndex" class="size-4" />
                                    <span v-else class="text-xs font-semibold">{{ i + 1 }}</span>
                                </span>
                                <span :class="cn('text-xs font-medium capitalize', i <= currentIndex ? 'text-foreground' : 'text-muted-foreground')">
                                    {{ step }}
                                </span>
                            </div>
                            <div
                                v-if="i < flow.length - 1"
                                :class="cn('mx-1 h-0.5 flex-1 rounded-full sm:mx-2', i < currentIndex ? 'bg-primary' : 'bg-muted')"
                            ></div>
                        </template>
                    </div>
                </div>

                <!-- Items -->
                <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                    <h2 class="border-b px-5 py-4 font-display text-lg font-semibold">Items</h2>
                    <div class="divide-y">
                        <div v-for="item in order.items" :key="item.id" class="flex items-center gap-4 p-4">
                            <div class="size-14 shrink-0 overflow-hidden rounded-lg border bg-secondary/40">
                                <img
                                    v-if="item.product?.images?.[0]?.url"
                                    :src="item.product.images[0].url"
                                    class="h-full w-full object-cover"
                                />
                                <BottleThumb v-else :name="item.product_name" :category="item.product?.category?.name" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ item.product_name }}</p>
                                <p class="text-sm text-muted-foreground">{{ formatMoney(item.unit_price) }} × {{ item.quantity }}</p>
                            </div>
                            <span class="font-display font-semibold">{{ formatMoney(item.line_total) }}</span>
                        </div>
                    </div>
                    <div class="space-y-1.5 border-t px-5 py-4 text-sm">
                        <div class="flex justify-between text-muted-foreground">
                            <span>Subtotal</span>
                            <span>{{ formatMoney(order.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-muted-foreground">
                            <span>Delivery</span>
                            <span class="text-emerald-600">Free</span>
                        </div>
                        <div class="flex justify-between pt-1.5 text-base font-semibold">
                            <span>Total</span>
                            <span class="font-display">{{ formatMoney(order.total) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Update status -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <h2 class="mb-3 font-display text-lg font-semibold">Update status</h2>
                    <select
                        v-model="status"
                        class="h-11 w-full rounded-lg border bg-background px-3 text-sm capitalize outline-none focus:border-primary"
                    >
                        <option v-for="s in statuses" :key="s" :value="s" class="capitalize">{{ s }}</option>
                    </select>
                    <Button class="mt-3 w-full" :disabled="status === order.status" @click="updateStatus">
                        Save status
                    </Button>
                </div>

                <!-- Customer -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 font-display text-lg font-semibold">Customer &amp; delivery</h2>
                    <ul class="space-y-4 text-sm">
                        <li class="flex gap-3">
                            <UserIcon class="mt-0.5 size-4 shrink-0 text-primary" />
                            <div>
                                <p class="text-xs text-muted-foreground">Name</p>
                                <p class="font-medium">{{ order.customer_name }}</p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <Phone class="mt-0.5 size-4 shrink-0 text-primary" />
                            <div>
                                <p class="text-xs text-muted-foreground">Contact</p>
                                <p class="font-medium">{{ order.contact }}</p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <MapPin class="mt-0.5 size-4 shrink-0 text-primary" />
                            <div>
                                <p class="text-xs text-muted-foreground">Address</p>
                                <p class="font-medium whitespace-pre-line">{{ order.shipping_address }}</p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <Banknote class="mt-0.5 size-4 shrink-0 text-primary" />
                            <div>
                                <p class="text-xs text-muted-foreground">Payment</p>
                                <p class="font-medium uppercase">{{ order.payment_method }}</p>
                            </div>
                        </li>
                        <li v-if="order.note" class="flex gap-3">
                            <StickyNote class="mt-0.5 size-4 shrink-0 text-primary" />
                            <div>
                                <p class="text-xs text-muted-foreground">Note</p>
                                <p class="font-medium">{{ order.note }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
