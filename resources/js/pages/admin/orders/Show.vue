<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatMoney } from '@/lib/format';
import type { Order } from '@/types/shop';

const props = defineProps<{
    order: Order;
    statuses: string[];
}>();

const status = ref(props.order.status);

function updateStatus(): void {
    router.patch(
        `/admin/orders/${props.order.id}/status`,
        { status: status.value },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="order.order_number" />
    <AdminLayout :title="order.order_number">
        <Link
            href="/admin/orders"
            class="mb-4 inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> Back to orders
        </Link>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-xl border bg-card shadow-sm">
                    <h2 class="border-b p-4 font-semibold">Items</h2>
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
                    <div
                        class="flex items-center justify-between border-t p-4 font-semibold"
                    >
                        <span>Total</span>
                        <span>{{ formatMoney(order.total) }}</span>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <h2 class="mb-3 font-semibold">Status</h2>
                    <select
                        v-model="status"
                        class="h-10 w-full rounded-md border bg-background px-3 text-sm capitalize outline-none focus:border-primary"
                    >
                        <option
                            v-for="s in statuses"
                            :key="s"
                            :value="s"
                            class="capitalize"
                        >
                            {{ s }}
                        </option>
                    </select>
                    <Button
                        class="mt-3 w-full"
                        :disabled="status === order.status"
                        @click="updateStatus"
                        >Update status</Button
                    >
                </div>

                <div class="rounded-xl border bg-card p-4 text-sm shadow-sm">
                    <h2 class="mb-3 font-semibold">Customer & delivery</h2>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-muted-foreground">Name</dt>
                            <dd>{{ order.customer_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Contact</dt>
                            <dd>{{ order.contact }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Address</dt>
                            <dd class="whitespace-pre-line">
                                {{ order.shipping_address }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Payment</dt>
                            <dd class="uppercase">
                                {{ order.payment_method }}
                            </dd>
                        </div>
                        <div v-if="order.note">
                            <dt class="text-muted-foreground">Note</dt>
                            <dd>{{ order.note }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
