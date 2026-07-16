<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, Clock, XCircle } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Order } from '@/types/shop';

const props = defineProps<{
    order: Order;
}>();

const statusTone: Record<string, string> = {
    pending: 'bg-amber-500/15 text-amber-600',
    processing: 'bg-blue-500/15 text-blue-600',
    shipped: 'bg-violet-500/15 text-violet-600',
    delivered: 'bg-emerald-500/15 text-emerald-600',
    cancelled: 'bg-red-500/15 text-red-600',
};

// Live countdown for the cancellation window so the button disappears the
// moment the 5 minutes are up, without needing a page refresh.
const now = ref(Date.now());
let timer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    timer = setInterval(() => (now.value = Date.now()), 1000);
});

onBeforeUnmount(() => {
    if (timer) {
        clearInterval(timer);
    }
});

const secondsLeft = computed(() => {
    if (!props.order.cancellable_until) {
        return 0;
    }

    return Math.max(0, Math.floor((new Date(props.order.cancellable_until).getTime() - now.value) / 1000));
});

const canCancel = computed(() => props.order.can_cancel && secondsLeft.value > 0);

const countdown = computed(() => {
    const minutes = Math.floor(secondsLeft.value / 60);
    const seconds = String(secondsLeft.value % 60).padStart(2, '0');

    return `${minutes}:${seconds}`;
});

function cancelOrder(): void {
    if (confirm('Cancel this order? The items will be returned to stock.')) {
        router.post(`/orders/${props.order.id}/cancel`, {}, { preserveScroll: true });
    }
}
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
                v-if="order.status === 'cancelled'"
                class="mb-4 flex items-center gap-3 rounded-xl border border-red-500/30 bg-red-500/5 p-4"
            >
                <XCircle class="size-6 text-red-600" />
                <div>
                    <p class="font-medium">Order cancelled</p>
                    <p class="text-sm text-muted-foreground">
                        This order was cancelled and will not be delivered.
                    </p>
                </div>
            </div>
            <div
                v-else
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

            <!-- Cancellation window -->
            <div
                v-if="canCancel"
                class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-500/30 bg-amber-500/5 p-4"
            >
                <div class="flex items-center gap-3">
                    <Clock class="size-6 text-amber-600" />
                    <div>
                        <p class="font-medium">Changed your mind?</p>
                        <p class="text-sm text-muted-foreground">
                            You can cancel this order for another
                            <span class="font-semibold text-amber-600 tabular-nums">{{ countdown }}</span>
                            minutes.
                        </p>
                    </div>
                </div>
                <Button variant="destructive" @click="cancelOrder">Cancel order</Button>
            </div>
            <div
                v-else-if="order.status === 'pending'"
                class="mb-4 flex items-center gap-3 rounded-xl border bg-muted/40 p-4"
            >
                <Clock class="size-6 text-muted-foreground" />
                <p class="text-sm text-muted-foreground">
                    This order can no longer be cancelled — orders can only be cancelled within
                    <span class="font-semibold">5 minutes</span> of being placed. Please contact us if you need help.
                </p>
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
