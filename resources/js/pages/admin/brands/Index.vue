<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Package, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { Spinner } from '@/components/ui/spinner';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    destroy as destroyBrand,
    index as adminBrands,
    store as storeBrand,
    update as updateBrand,
} from '@/routes/admin/brands';
import type { Brand, Paginated } from '@/types/shop';

/** How long typing has to pause before the table reloads. */
const SEARCH_DEBOUNCE_MS = 300;

const props = defineProps<{
    brands: Paginated<Brand>;
    filters: { search: string };
}>();

const search = ref(props.filters.search ?? '');
const dialogOpen = ref(false);
const editing = ref<Brand | null>(null);
const isFiltering = ref(false);
let searchTimer: ReturnType<typeof setTimeout> | undefined;

const isEdit = computed(() => editing.value !== null);

const inputClass =
    'h-11 w-full rounded-lg border bg-background px-3.5 text-sm outline-none transition-colors focus:border-primary';

const form = useForm({ name: '' });

function applyFilters(): void {
    clearTimeout(searchTimer);

    router.get(
        adminBrands.url(),
        { search: search.value || undefined },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['brands', 'filters'],
            onStart: () => {
                isFiltering.value = true;
            },
            onFinish: () => {
                isFiltering.value = false;
            },
        },
    );
}

// Filter the table as the admin types, rather than waiting for Enter.
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, SEARCH_DEBOUNCE_MS);
});

onBeforeUnmount(() => clearTimeout(searchTimer));

function openCreate(): void {
    editing.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(brand: Brand): void {
    editing.value = brand;
    form.reset();
    form.clearErrors();
    form.name = brand.name;
    dialogOpen.value = true;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset();
        },
    };

    if (isEdit.value) {
        form.put(updateBrand.url(editing.value!.id), options);

        return;
    }

    form.post(storeBrand.url(), options);
}

function destroy(brand: Brand): void {
    const count = brand.products_count ?? 0;
    const warning = count > 0
        ? `Delete "${brand.name}"? ${count} product(s) will be left without a brand.`
        : `Delete "${brand.name}"?`;

    if (confirm(warning)) {
        router.delete(destroyBrand.url(brand.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Brands" />
    <AdminLayout title="Brands">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative flex-1 sm:max-w-xs">
                <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search brands…"
                    class="h-11 w-full rounded-lg border bg-card pr-9 pl-9 text-sm outline-none transition-colors focus:border-primary"
                />
                <Spinner
                    v-if="isFiltering"
                    class="absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
            </div>
            <Button class="h-11 gap-1.5" @click="openCreate">
                <Plus class="size-4" /> New brand
            </Button>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-[11px] font-semibold tracking-[0.1em] text-muted-foreground uppercase">
                            <th class="px-5 py-3.5">Brand</th>
                            <th class="px-5 py-3.5">Products</th>
                            <th class="hidden px-5 py-3.5 md:table-cell">Slug</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="brand in brands.data" :key="brand.id" class="transition-colors hover:bg-muted/40">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-11 shrink-0 items-center justify-center rounded-lg border bg-secondary/40 text-muted-foreground">
                                        <Package class="size-5" />
                                    </div>
                                    <button class="truncate font-medium transition-colors hover:text-primary" @click="openEdit(brand)">
                                        {{ brand.name }}
                                    </button>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                                    {{ brand.products_count ?? 0 }} products
                                </span>
                            </td>
                            <td class="hidden px-5 py-3.5 text-muted-foreground md:table-cell">{{ brand.slug }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1">
                                    <Button size="icon" variant="ghost" @click="openEdit(brand)">
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button size="icon" variant="ghost" class="text-red-600 hover:text-red-600" @click="destroy(brand)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!brands.data.length">
                            <td colspan="4" class="px-5 py-12 text-center text-muted-foreground">No brands yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <Pagination :links="brands.links" />
        </div>

        <!-- Create / edit modal -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="font-display text-xl">{{ isEdit ? 'Edit brand' : 'New brand' }}</DialogTitle>
                    <DialogDescription>Brands label your products in the storefront and can be searched.</DialogDescription>
                </DialogHeader>

                <form class="grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-1.5">
                        <Label for="brand-name">Name</Label>
                        <input id="brand-name" v-model="form.name" :class="inputClass" placeholder="e.g. Jack Daniel's" required />
                        <InputError :message="form.errors.name" />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="dialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">{{ isEdit ? 'Save changes' : 'Create brand' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
