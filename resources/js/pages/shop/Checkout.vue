<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Banknote } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { formatMoney } from '@/lib/format';
import { store as placeOrder } from '@/routes/shop/checkout';

type CheckoutItem = {
    id: number;
    quantity: number;
    name: string;
    final_price: number;
    image: string | null;
};

const props = defineProps<{
    items: CheckoutItem[];
    totals: { subtotal: number; total: number; count: number };
    customer: { customer_name: string; contact: string | null };
}>();

const inputClass =
    'h-10 w-full rounded-md border bg-background px-3 text-sm outline-none focus:border-primary';

const form = useForm({
    customer_name: props.customer.customer_name ?? '',
    contact: props.customer.contact ?? '',
    shipping_address: '',
    note: '',
});

function submit(): void {
    form.post(placeOrder.url());
}
</script>

<template>
    <Head title="Checkout" />
    <ShopLayout>
        <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6">
            <h1 class="mb-6 font-display text-3xl font-semibold">Checkout</h1>

            <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
                <div class="space-y-4 lg:col-span-2">
                    <div class="rounded-xl border bg-card p-5">
                        <h2 class="mb-4 font-semibold">Delivery details</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label for="customer_name">Full name</Label>
                                <input
                                    id="customer_name"
                                    v-model="form.customer_name"
                                    :class="inputClass"
                                />
                                <InputError
                                    :message="form.errors.customer_name"
                                />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="contact">Contact number</Label>
                                <input
                                    id="contact"
                                    v-model="form.contact"
                                    :class="inputClass"
                                />
                                <InputError :message="form.errors.contact" />
                            </div>
                            <div class="grid gap-1.5 sm:col-span-2">
                                <Label for="shipping_address"
                                    >Shipping address</Label
                                >
                                <textarea
                                    id="shipping_address"
                                    v-model="form.shipping_address"
                                    rows="3"
                                    placeholder="Street, city, postal code"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:border-primary"
                                />
                                <InputError
                                    :message="form.errors.shipping_address"
                                />
                            </div>
                            <div class="grid gap-1.5 sm:col-span-2">
                                <Label for="note">Order note (optional)</Label>
                                <input
                                    id="note"
                                    v-model="form.note"
                                    :class="inputClass"
                                    placeholder="Delivery instructions..."
                                />
                                <InputError :message="form.errors.note" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card p-5">
                        <h2 class="mb-3 font-semibold">Payment method</h2>
                        <div
                            class="flex items-center gap-3 rounded-lg border border-primary bg-primary/5 p-4"
                        >
                            <Banknote class="size-6 text-primary" />
                            <div>
                                <p class="font-medium">Cash on Delivery</p>
                                <p class="text-sm text-muted-foreground">
                                    Pay with cash when your order arrives.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="h-fit rounded-xl border bg-card p-5">
                    <h2 class="mb-4 font-semibold">Your order</h2>
                    <div class="space-y-3">
                        <div
                            v-for="item in items"
                            :key="item.id"
                            class="flex items-center gap-3"
                        >
                            <img
                                :src="item.image ?? 'https://placehold.co/60'"
                                class="size-12 rounded-md border object-cover"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ item.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Qty {{ item.quantity }}
                                </p>
                            </div>
                            <span class="text-sm font-medium">{{
                                formatMoney(item.final_price * item.quantity)
                            }}</span>
                        </div>
                    </div>
                    <dl class="mt-4 space-y-2 border-t pt-4 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Subtotal</dt>
                            <dd>{{ formatMoney(totals.subtotal) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Delivery</dt>
                            <dd class="text-emerald-600">Free</dd>
                        </div>
                        <div
                            class="flex justify-between text-base font-semibold"
                        >
                            <dt>Total</dt>
                            <dd>{{ formatMoney(totals.total) }}</dd>
                        </div>
                    </dl>
                    <Button
                        type="submit"
                        class="mt-4 w-full"
                        :disabled="form.processing"
                        >Place order</Button
                    >
                </div>
            </form>
        </div>
    </ShopLayout>
</template>
