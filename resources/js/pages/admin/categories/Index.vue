<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ImagePlus, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import BottleThumb from '@/components/shop/BottleThumb.vue';
import Pagination from '@/components/shop/Pagination.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { destroy as destroyCategory } from '@/routes/admin/categories';
import type { Category, Paginated } from '@/types/shop';

const props = defineProps<{
    categories: Paginated<Category>;
    filters: { search: string };
}>();

const search = ref(props.filters.search ?? '');
const dialogOpen = ref(false);
const editing = ref<Category | null>(null);
const preview = ref<string | null>(null);

const isEdit = computed(() => editing.value !== null);

const inputClass =
    'h-11 w-full rounded-lg border bg-background px-3.5 text-sm outline-none transition-colors focus:border-primary';

const form = useForm({
    _method: 'post',
    name: '',
    description: '',
    image: null as File | null,
});

function applyFilters(): void {
    router.get(
        '/admin/categories',
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function openCreate(): void {
    editing.value = null;
    preview.value = null;
    form.reset();
    form.clearErrors();
    form._method = 'post';
    dialogOpen.value = true;
}

function openEdit(category: Category): void {
    editing.value = category;
    preview.value = category.image ?? null;
    form.reset();
    form.clearErrors();
    form._method = 'put';
    form.name = category.name;
    form.description = category.description ?? '';
    dialogOpen.value = true;
}

function onFile(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.image = file;
    preview.value = file ? URL.createObjectURL(file) : (editing.value?.image ?? null);
}

function submit(): void {
    const url = isEdit.value
        ? `/admin/categories/${editing.value!.id}`
        : '/admin/categories';

    form.post(url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset();
        },
    });
}

function destroy(category: Category): void {
    if (confirm(`Delete "${category.name}"? Products in it will be removed too.`)) {
        router.delete(destroyCategory.url(category.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Categories" />
    <AdminLayout title="Categories">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative flex-1 sm:max-w-xs">
                <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search categories…"
                    class="h-11 w-full rounded-lg border bg-card pr-3 pl-9 text-sm outline-none transition-colors focus:border-primary"
                    @keyup.enter="applyFilters"
                />
            </div>
            <Button class="h-11 gap-1.5" @click="openCreate">
                <Plus class="size-4" /> New category
            </Button>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-[11px] font-semibold tracking-[0.1em] text-muted-foreground uppercase">
                            <th class="px-5 py-3.5">Category</th>
                            <th class="px-5 py-3.5">Products</th>
                            <th class="hidden px-5 py-3.5 md:table-cell">Description</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="category in categories.data" :key="category.id" class="transition-colors hover:bg-muted/40">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="size-11 shrink-0 overflow-hidden rounded-lg border bg-secondary/40">
                                        <img
                                            v-if="category.image"
                                            :src="category.image"
                                            :alt="category.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <BottleThumb v-else :name="category.name" :category="category.name" />
                                    </div>
                                    <div class="min-w-0">
                                        <button class="block truncate font-medium transition-colors hover:text-primary" @click="openEdit(category)">
                                            {{ category.name }}
                                        </button>
                                        <p class="truncate text-xs text-muted-foreground">{{ category.slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                                    {{ category.products_count ?? 0 }} products
                                </span>
                            </td>
                            <td class="hidden max-w-md px-5 py-3.5 text-muted-foreground md:table-cell">
                                <p class="line-clamp-1">{{ category.description ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1">
                                    <Button size="icon" variant="ghost" @click="openEdit(category)">
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button size="icon" variant="ghost" class="text-red-600 hover:text-red-600" @click="destroy(category)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!categories.data.length">
                            <td colspan="4" class="px-5 py-12 text-center text-muted-foreground">No categories yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <Pagination :links="categories.links" />
        </div>

        <!-- Create / edit modal -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="font-display text-xl">{{ isEdit ? 'Edit category' : 'New category' }}</DialogTitle>
                    <DialogDescription>Categories group your products in the storefront.</DialogDescription>
                </DialogHeader>

                <form class="grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-1.5">
                        <Label for="name">Name</Label>
                        <input id="name" v-model="form.name" :class="inputClass" placeholder="e.g. Whisky" required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="description">Description</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-lg border bg-background px-3.5 py-2.5 text-sm outline-none transition-colors focus:border-primary"
                            placeholder="Short description shown to customers"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label>Image</Label>
                        <div class="flex items-center gap-4">
                            <div class="size-20 shrink-0 overflow-hidden rounded-lg border bg-secondary/40">
                                <img v-if="preview" :src="preview" class="h-full w-full object-cover" />
                                <div v-else class="flex h-full w-full items-center justify-center text-muted-foreground">
                                    <ImagePlus class="size-5" />
                                </div>
                            </div>
                            <input type="file" accept="image/*" class="text-sm" @change="onFile" />
                        </div>
                        <InputError :message="form.errors.image" />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="dialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">{{ isEdit ? 'Save changes' : 'Create category' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
