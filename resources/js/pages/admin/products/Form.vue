<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ImagePlus, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
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
    'h-10 w-full rounded-md border bg-background px-3 text-sm outline-none focus:border-primary';

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
    existingImages.value = existingImages.value.filter(
        (image) => image.id !== id,
    );
}

function submit(): void {
    const url = isEdit.value
        ? `/admin/products/${props.product!.id}`
        : '/admin/products';
    form.post(url, { forceFormData: true });
}
</script>

<template>
    <Head :title="isEdit ? 'Edit product' : 'New product'" />
    <AdminLayout :title="isEdit ? 'Edit product' : 'New product'">
        <Link
            href="/admin/products"
            class="mb-4 inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> Back to products
        </Link>

        <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 font-semibold">Details</h2>
                    <div class="grid gap-4">
                        <div class="grid gap-1.5">
                            <Label for="name">Name</Label>
                            <input
                                id="name"
                                v-model="form.name"
                                :class="inputClass"
                                required
                            />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label for="brand">Brand</Label>
                                <input
                                    id="brand"
                                    v-model="form.brand"
                                    :class="inputClass"
                                />
                                <InputError :message="form.errors.brand" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="category">Category</Label>
                                <select
                                    id="category"
                                    v-model="form.category_id"
                                    :class="inputClass"
                                    required
                                >
                                    <option
                                        v-for="c in categories"
                                        :key="c.id"
                                        :value="c.id"
                                    >
                                        {{ c.name }}
                                    </option>
                                </select>
                                <InputError
                                    :message="form.errors.category_id"
                                />
                            </div>
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="5"
                                class="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:border-primary"
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 font-semibold">Images</h2>
                    <div class="flex flex-wrap gap-3">
                        <div
                            v-for="image in existingImages"
                            :key="image.id"
                            class="group relative"
                        >
                            <img
                                :src="image.url"
                                class="size-24 rounded-lg border object-cover"
                            />
                            <button
                                type="button"
                                class="absolute -top-2 -right-2 flex size-6 items-center justify-center rounded-full bg-red-500 text-white opacity-0 transition-opacity group-hover:opacity-100"
                                @click="removeExisting(image.id)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </div>
                        <div
                            v-for="(preview, index) in newPreviews"
                            :key="`new-${index}`"
                            class="group relative"
                        >
                            <img
                                :src="preview"
                                class="size-24 rounded-lg border object-cover"
                            />
                            <button
                                type="button"
                                class="absolute -top-2 -right-2 flex size-6 items-center justify-center rounded-full bg-red-500 text-white opacity-0 transition-opacity group-hover:opacity-100"
                                @click="removeNew(index)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </div>
                        <label
                            class="flex size-24 cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border border-dashed text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                        >
                            <ImagePlus class="size-5" />
                            <span class="text-xs">Add</span>
                            <input
                                type="file"
                                accept="image/*"
                                multiple
                                class="hidden"
                                @change="onFiles"
                            />
                        </label>
                    </div>
                    <InputError class="mt-2" :message="form.errors.images" />
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 font-semibold">Pricing & stock</h2>
                    <div class="grid gap-4">
                        <div class="grid gap-1.5">
                            <Label for="price">Price ($)</Label>
                            <input
                                id="price"
                                v-model="form.price"
                                type="number"
                                step="0.01"
                                min="0"
                                :class="inputClass"
                                required
                            />
                            <InputError :message="form.errors.price" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="discount">Discount (%)</Label>
                            <input
                                id="discount"
                                v-model.number="form.discount_percent"
                                type="number"
                                min="0"
                                max="100"
                                :class="inputClass"
                            />
                            <InputError
                                :message="form.errors.discount_percent"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="stock">Stock</Label>
                            <input
                                id="stock"
                                v-model.number="form.stock"
                                type="number"
                                min="0"
                                :class="inputClass"
                            />
                            <InputError :message="form.errors.stock" />
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="size-4 rounded border-muted-foreground/40"
                            />
                            Visible in store
                        </label>
                    </div>
                </div>

                <Button
                    type="submit"
                    class="w-full"
                    :disabled="form.processing"
                >
                    {{ isEdit ? 'Save changes' : 'Create product' }}
                </Button>
            </div>
        </form>
    </AdminLayout>
</template>
