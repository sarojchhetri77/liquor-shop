<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Pagination from '@/components/shop/Pagination.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { Category, Paginated } from '@/types/shop';

const props = defineProps<{
    categories: Paginated<Category>;
    filters: { search: string };
}>();

const search = ref(props.filters.search ?? '');

function applyFilters(): void {
    router.get(
        '/admin/categories',
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function destroy(category: Category): void {
    if (
        confirm(
            `Delete "${category.name}"? Products in it will be removed too.`,
        )
    ) {
        router.delete(`/admin/categories/${category.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Categories" />
    <AdminLayout title="Categories">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="relative flex-1 sm:max-w-xs">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search categories..."
                    class="h-10 w-full rounded-md border bg-background pr-3 pl-9 text-sm outline-none focus:border-primary"
                    @keyup.enter="applyFilters"
                />
            </div>
            <Button as-child>
                <Link href="/admin/categories/create" class="gap-1.5"
                    ><Plus class="size-4" /> New category</Link
                >
            </Button>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="category in categories.data"
                :key="category.id"
                class="overflow-hidden rounded-xl border bg-card shadow-sm"
            >
                <div class="aspect-[16/9] bg-muted">
                    <img
                        :src="
                            category.image ??
                            'https://placehold.co/600x340?text=' +
                                encodeURIComponent(category.name)
                        "
                        :alt="category.name"
                        class="h-full w-full object-cover"
                    />
                </div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-medium">
                                {{ category.name }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ category.products_count ?? 0 }} products
                            </p>
                        </div>
                        <div class="flex gap-1">
                            <Button as-child size="icon" variant="ghost">
                                <Link
                                    :href="`/admin/categories/${category.id}/edit`"
                                    ><Pencil class="size-4"
                                /></Link>
                            </Button>
                            <Button
                                size="icon"
                                variant="ghost"
                                class="text-red-600 hover:text-red-600"
                                @click="destroy(category)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                    <p
                        v-if="category.description"
                        class="mt-2 line-clamp-2 text-sm text-muted-foreground"
                    >
                        {{ category.description }}
                    </p>
                </div>
            </div>
            <p
                v-if="!categories.data.length"
                class="col-span-full rounded-xl border border-dashed p-10 text-center text-muted-foreground"
            >
                No categories yet.
            </p>
        </div>

        <div class="mt-4">
            <Pagination :links="categories.links" />
        </div>
    </AdminLayout>
</template>
