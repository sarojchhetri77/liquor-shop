<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ImagePlus, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Product } from '@/types/shop';

const props = defineProps<{
    product?: Product;
    categories: { id: number; name: string }[];
}>();

const isEdit = computed(() => !!props.product);
const existingImages = ref(props.product?.images ?? []);
const newPreviews = ref<string[]>([]);

const form = useForm({
    category_id: props.product?.category_id ?? props.categories[0]?.id ?? '',
    name: props.product?.name ?? '',
    brand: props.product?.brand ?? '',
    description: props.product?.description ?? '',
    price: props.product?.price ?? '',
    discount_percent: props.product?.discount_percent ?? 0,
    stock: props.product?.stock ?? 0,
    is_active: props.product?.is_active ?? true,
    images: [] as File[],
    removed_image_ids: [] as number[],
});

const inputClass =
    'h-11 w-full rounded-lg border bg-background px-3.5 text-sm outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/15';

const finalPrice = computed(() => {
    const price = parseFloat(String(form.price)) || 0;
    const discount = Number(form.discount_percent) || 0;

    return Math.round(price * (1 - discount / 100));
});

const totalImages = computed(() => existingImages.value.length + newPreviews.value.length);

function onFiles(event: Event): void {
    const files = Array.from((event.target as HTMLInputElement).files ?? []);
    form.images = [...form.images, ...files];
    newPreviews.value = form.images.map((file) => URL.createObjectURL(file));
}

function removeNew(index: number): void {
    form.images.splice(index, 1);
    newPreviews.value = form.images.map((file) => URL.createObjectURL(file));
}

function removeExisting(id: number): void {
    form.removed_image_ids.push(id);
    existingImages.value = existingImages.value.filter((image) => image.id !== id);
}

function submit(): void {
    const url = isEdit.value ? `/admin/products/${props.product!.id}` : '/admin/products';
    form.post(url, { forceFormData: true });
}
</script>

<template>
    <Head :title="isEdit ? 'Edit product' : 'New product'" />
    <AdminLayout :title="isEdit ? 'Edit product' : 'New product'">
        <!-- Sticky action header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <Link
                href="/admin/products"
                class="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4" /> Back to products
            </Link>
            <div class="flex items-center gap-2">
                <Button as-child variant="ghost"><Link href="/admin/products">Cancel</Link></Button>
                <Button type="submit" form="product-form" :disabled="form.processing" class="px-6">
                    {{ isEdit ? 'Save changes' : 'Create product' }}
                </Button>
            </div>
        </div>

        <form id="product-form" class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <div class="space-y-6 lg:col-span-2">
                <!-- Details -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <h2 class="font-display text-lg font-semibold">Details</h2>
                    <p class="mb-5 text-sm text-muted-foreground">Basic product information shown to customers.</p>
                    <div class="grid gap-5">
                        <div class="grid gap-1.5">
                            <Label for="name">Product name</Label>
                            <input id="name" v-model="form.name" :class="inputClass" placeholder="e.g. Jack Daniel's Old No.7 750ML" required />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label for="brand">Brand</Label>
                                <input id="brand" v-model="form.brand" :class="inputClass" placeholder="e.g. Jack Daniel's" />
                                <InputError :message="form.errors.brand" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="category">Category</Label>
                                <select id="category" v-model="form.category_id" :class="inputClass" required>
                                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                                <InputError :message="form.errors.category_id" />
                            </div>
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="5"
                                class="w-full rounded-lg border bg-background px-3.5 py-2.5 text-sm outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/15"
                                placeholder="Tasting notes, volume, ABV…"
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <h2 class="font-display text-lg font-semibold">Images</h2>
                    <p class="mb-5 text-sm text-muted-foreground">The first image is used as the product thumbnail.</p>
                    <div class="flex flex-wrap gap-3">
                        <div v-for="(image, i) in existingImages" :key="image.id" class="group relative">
                            <img :src="image.url" class="size-28 rounded-xl border object-cover" />
                            <span
                                v-if="i === 0"
                                class="absolute bottom-1.5 left-1.5 rounded-full bg-primary px-2 py-0.5 text-[10px] font-semibold text-primary-foreground"
                            >
                                Primary
                            </span>
                            <button
                                type="button"
                                class="absolute -top-2 -right-2 flex size-6 items-center justify-center rounded-full bg-red-500 text-white shadow opacity-0 transition-opacity group-hover:opacity-100"
                                @click="removeExisting(image.id)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </div>
                        <div v-for="(preview, index) in newPreviews" :key="`new-${index}`" class="group relative">
                            <img :src="preview" class="size-28 rounded-xl border object-cover" />
                            <button
                                type="button"
                                class="absolute -top-2 -right-2 flex size-6 items-center justify-center rounded-full bg-red-500 text-white shadow opacity-0 transition-opacity group-hover:opacity-100"
                                @click="removeNew(index)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </div>
                        <label
                            class="flex size-28 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed text-muted-foreground transition-colors hover:border-primary hover:bg-accent/40 hover:text-primary"
                        >
                            <ImagePlus class="size-5" />
                            <span class="text-xs font-medium">Add image</span>
                            <input type="file" accept="image/*" multiple class="hidden" @change="onFiles" />
                        </label>
                    </div>
                    <p v-if="totalImages === 0" class="mt-3 text-xs text-muted-foreground">
                        No image yet — a branded placeholder will be shown in the store.
                    </p>
                    <InputError class="mt-2" :message="form.errors.images" />
                </div>
            </div>

            <!-- Pricing sidebar -->
            <div class="space-y-6">
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <h2 class="mb-5 font-display text-lg font-semibold">Pricing &amp; stock</h2>
                    <div class="grid gap-5">
                        <div class="grid gap-1.5">
                            <Label for="price">Price</Label>
                            <div class="relative">
                                <span class="absolute top-1/2 left-3.5 -translate-y-1/2 text-sm font-medium text-muted-foreground">Rs.</span>
                                <input id="price" v-model="form.price" type="number" step="0.01" min="0" :class="cn(inputClass, 'pl-11')" required />
                            </div>
                            <InputError :message="form.errors.price" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="discount">Discount (%)</Label>
                            <input id="discount" v-model.number="form.discount_percent" type="number" min="0" max="100" :class="inputClass" />
                            <InputError :message="form.errors.discount_percent" />
                        </div>

                        <div
                            v-if="Number(form.discount_percent) > 0 && Number(form.price) > 0"
                            class="flex items-center justify-between rounded-lg bg-accent/50 px-3.5 py-2.5 text-sm"
                        >
                            <span class="text-muted-foreground">Customer pays</span>
                            <span class="font-display text-base font-semibold text-primary">{{ formatMoney(finalPrice) }}</span>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="stock">Stock</Label>
                            <input id="stock" v-model.number="form.stock" type="number" min="0" :class="inputClass" />
                            <InputError :message="form.errors.stock" />
                        </div>

                        <div class="flex items-center justify-between rounded-lg border px-3.5 py-3">
                            <div>
                                <p class="text-sm font-medium">Visible in store</p>
                                <p class="text-xs text-muted-foreground">Show this product to customers</p>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="form.is_active"
                                :class="cn('relative h-6 w-11 shrink-0 rounded-full transition-colors', form.is_active ? 'bg-primary' : 'bg-muted-foreground/30')"
                                @click="form.is_active = !form.is_active"
                            >
                                <span :class="cn('absolute top-0.5 size-5 rounded-full bg-white shadow transition-transform', form.is_active ? 'translate-x-[22px]' : 'translate-x-0.5')"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <Button type="submit" class="w-full" :disabled="form.processing">
                    {{ isEdit ? 'Save changes' : 'Create product' }}
                </Button>
            </div>
        </form>
    </AdminLayout>
</template>
