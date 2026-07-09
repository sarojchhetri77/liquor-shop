<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Tag, Trash2 } from '@lucide/vue';
import { reactive, ref } from 'vue';
import Pagination from '@/components/shop/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatMoney } from '@/lib/format';
import type { Paginated, Product } from '@/types/shop';

const props = defineProps<{
    products: Paginated<Product>;
    categories: { id: number; name: string }[];
    filters: { search: string; category_id: number | null };
}>();

const filters = reactive({
    search: props.filters.search ?? '',
    category_id: props.filters.category_id ?? '',
});

const selected = ref<number[]>([]);
const discountValue = ref<number | null>(null);

function applyFilters(): void {
    router.get(
        '/admin/products',
        {
            search: filters.search || undefined,
            category_id: filters.category_id || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function toggle(id: number): void {
    const index = selected.value.indexOf(id);

    if (index === -1) {
        selected.value.push(id);
    } else {
        selected.value.splice(index, 1);
    }
}

function applyDiscount(): void {
    if (!selected.value.length || discountValue.value === null) {
        return;
    }

    router.post(
        '/admin/products/discount',
        { product_ids: selected.value, discount_percent: discountValue.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selected.value = [];
                discountValue.value = null;
            },
        },
    );
}

function destroy(product: Product): void {
    if (confirm(`Delete "${product.name}"? This cannot be undone.`)) {
        router.delete(`/admin/products/${product.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Products" />
    <AdminLayout title="Products">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex flex-1 flex-col gap-2 sm:flex-row">
                <div class="relative flex-1 sm:max-w-xs">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        v-model="filters.search"
                        type="search"
                        placeholder="Search by name..."
                        class="h-10 w-full rounded-md border bg-background pr-3 pl-9 text-sm outline-none focus:border-primary"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <select
                    v-model="filters.category_id"
                    class="h-10 rounded-md border bg-background px-3 text-sm outline-none focus:border-primary"
                    @change="applyFilters"
                >
                    <option value="">All categories</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>
                <Button variant="secondary" @click="applyFilters"
                    >Filter</Button
                >
            </div>
            <Button as-child>
                <Link href="/admin/products/create" class="gap-1.5"
                    ><Plus class="size-4" /> New product</Link
                >
            </Button>
        </div>

        <!-- Bulk discount tool -->
        <div
            v-if="selected.length"
            class="mt-4 flex flex-wrap items-center gap-3 rounded-lg border border-primary/30 bg-primary/5 p-4"
        >
            <Tag class="size-4 text-primary" />
            <span class="text-sm font-medium"
                >{{ selected.length }} selected</span
            >
            <div class="flex items-center gap-2">
                <input
                    v-model.number="discountValue"
                    type="number"
                    min="0"
                    max="100"
                    placeholder="%"
                    class="h-9 w-20 rounded-md border bg-background px-2 text-sm outline-none focus:border-primary"
                />
                <Button
                    size="sm"
                    :disabled="discountValue === null"
                    @click="applyDiscount"
                    >Apply discount</Button
                >
                <Button size="sm" variant="ghost" @click="selected = []"
                    >Clear</Button
                >
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b bg-muted/40 text-left text-muted-foreground"
                    >
                        <tr>
                            <th class="w-10 p-3"></th>
                            <th class="p-3 font-medium">Product</th>
                            <th class="p-3 font-medium">Category</th>
                            <th class="p-3 font-medium">Price</th>
                            <th class="p-3 font-medium">Stock</th>
                            <th class="p-3 font-medium">Status</th>
                            <th class="p-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="p-3">
                                <input
                                    type="checkbox"
                                    class="size-4 rounded border-muted-foreground/40"
                                    :checked="selected.includes(product.id)"
                                    @change="toggle(product.id)"
                                />
                            </td>
                            <td class="p-3">
                                <div class="flex items-center gap-3">
                                    <img
                                        :src="
                                            product.images?.[0]?.url ??
                                            'https://placehold.co/80'
                                        "
                                        :alt="product.name"
                                        class="size-11 rounded-md border object-cover"
                                    />
                                    <div class="min-w-0">
                                        <p class="truncate font-medium">
                                            {{ product.name }}
                                        </p>
                                        <p
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {{ product.brand }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 text-muted-foreground">
                                {{ product.category?.name }}
                            </td>
                            <td class="p-3">
                                <div class="flex flex-col">
                                    <span class="font-medium">{{
                                        formatMoney(product.final_price)
                                    }}</span>
                                    <span
                                        v-if="product.discount_percent > 0"
                                        class="text-xs text-emerald-600"
                                    >
                                        -{{ product.discount_percent }}%
                                    </span>
                                </div>
                            </td>
                            <td class="p-3">
                                <span
                                    :class="
                                        product.stock > 0 ? '' : 'text-red-600'
                                    "
                                    >{{ product.stock }}</span
                                >
                            </td>
                            <td class="p-3">
                                <Badge
                                    :variant="
                                        product.is_active
                                            ? 'secondary'
                                            : 'outline'
                                    "
                                >
                                    {{
                                        product.is_active ? 'Active' : 'Hidden'
                                    }}
                                </Badge>
                            </td>
                            <td class="p-3">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <Button
                                        as-child
                                        size="icon"
                                        variant="ghost"
                                    >
                                        <Link
                                            :href="`/admin/products/${product.id}/edit`"
                                            ><Pencil class="size-4"
                                        /></Link>
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        class="text-red-600 hover:text-red-600"
                                        @click="destroy(product)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!products.data.length">
                            <td
                                colspan="7"
                                class="p-8 text-center text-muted-foreground"
                            >
                                No products found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <Pagination :links="products.links" />
        </div>
    </AdminLayout>
</template>
