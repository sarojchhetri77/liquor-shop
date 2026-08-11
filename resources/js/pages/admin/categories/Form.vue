<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { index as adminCategories, update as updateCategory, store as storeCategory } from '@/routes/admin/categories';
import type { Category } from '@/types/shop';

const props = defineProps<{
    category?: Category;
}>();

const isEdit = computed(() => !!props.category);
const preview = ref<string | null>(props.category?.image ?? null);

const form = useForm({
    _method: isEdit.value ? 'put' : 'post',
    name: props.category?.name ?? '',
    description: props.category?.description ?? '',
    image: null as File | null,
});

const inputClass =
    'h-10 w-full rounded-md border bg-background px-3 text-sm outline-none focus:border-primary';

function onFile(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.image = file;
    preview.value = file
        ? URL.createObjectURL(file)
        : (props.category?.image ?? null);
}

function submit(): void {
    const url = isEdit.value
        ? updateCategory.url(props.category!.id)
        : storeCategory.url();
    form.post(url, { forceFormData: true });
}
</script>

<template>
    <Head :title="isEdit ? 'Edit category' : 'New category'" />
    <AdminLayout :title="isEdit ? 'Edit category' : 'New category'">
        <Link
            :href="adminCategories()"
            class="mb-4 inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> Back to categories
        </Link>

        <form class="max-w-2xl space-y-4" @submit.prevent="submit">
            <div class="rounded-xl border bg-card p-5 shadow-sm">
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
                    <div class="grid gap-1.5">
                        <Label for="description">Description</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:border-primary"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="image">Image</Label>
                        <div class="flex items-center gap-4">
                            <img
                                v-if="preview"
                                :src="preview"
                                class="size-20 rounded-lg border object-cover"
                            />
                            <input
                                id="image"
                                type="file"
                                accept="image/*"
                                class="text-sm"
                                @change="onFile"
                            />
                        </div>
                        <InputError :message="form.errors.image" />
                    </div>
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    {{ isEdit ? 'Save changes' : 'Create category' }}
                </Button>
                <Button as-child variant="ghost">
                    <Link :href="adminCategories()">Cancel</Link>
                </Button>
            </div>
        </form>
    </AdminLayout>
</template>
