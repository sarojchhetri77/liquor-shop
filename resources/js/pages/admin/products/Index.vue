<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Tag, Trash2 } from '@lucide/vue';
import { reactive, ref } from 'vue';
import BottleThumb from '@/components/shop/BottleThumb.vue';
import Pagination from '@/components/shop/Pagination.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
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
const discountStartsAt = ref<string>('');
const discountEndsAt = ref<string>('');

const controlClass =
    'h-11 rounded-lg border bg-card px-3.5 text-sm outline-none transition-colors focus:border-primary';

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
        {
            product_ids: selected.value,
            discount_percent: discountValue.value,
            discount_starts_at: discountStartsAt.value || null,
            discount_ends_at: discountEndsAt.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selected.value = [];
                discountValue.value = null;
                discountStartsAt.value = '';
                discountEndsAt.value = '';
            },
        },
    );
}

function destroy(product: Product): void {
    if (confirm(`Delete "${product.name}"? This cannot be undone.`)) {
        router.delete(`/admin/products/${product.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Products" />
    <AdminLayout title="Products">
        <!-- Filter bar -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-2 sm:flex-row">
                <div class="relative flex-1 sm:max-w-xs">
                    <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="filters.search"
                        type="search"
                        placeholder="Search by name…"
                        :class="cn(controlClass, 'w-full pr-3 pl-9')"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <select v-model="filters.category_id" :class="controlClass" @change="applyFilters">
                    <option value="">All categories</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <Button variant="secondary" class="h-11" @click="applyFilters">Filter</Button>
            </div>
            <Button as-child class="h-11">
                <Link href="/admin/products/create" class="gap-1.5"><Plus class="size-4" /> New product</Link>
            </Button>
        </div>

        <!-- Bulk discount tool -->
        <div
            v-if="selected.length"
            class="mt-4 flex flex-wrap items-end gap-3 rounded-xl border border-primary/30 bg-primary/5 p-4"
        >
            <div class="flex items-center gap-2 self-center">
                <Tag class="size-4 text-primary" />
                <span class="text-sm font-medium">{{ selected.length }} selected</span>
            </div>
            <label class="flex flex-col gap-1 text-xs text-muted-foreground">
                Discount
                <input
                    v-model.number="discountValue"
                    type="number"
                    min="0"
                    max="100"
                    placeholder="%"
                    class="h-10 w-20 rounded-lg border bg-background px-2.5 text-sm outline-none focus:border-primary"
                />
            </label>
            <label class="flex flex-col gap-1 text-xs text-muted-foreground">
                Starts (optional)
                <input
                    v-model="discountStartsAt"
                    type="datetime-local"
                    class="h-10 rounded-lg border bg-background px-2.5 text-sm outline-none focus:border-primary"
                />
            </label>
            <label class="flex flex-col gap-1 text-xs text-muted-foreground">
                Expires (optional)
                <input
                    v-model="discountEndsAt"
                    type="datetime-local"
                    class="h-10 rounded-lg border bg-background px-2.5 text-sm outline-none focus:border-primary"
                />
            </label>
            <div class="flex items-center gap-2 self-center">
                <Button size="sm" :disabled="discountValue === null" @click="applyDiscount">Apply discount</Button>
                <Button size="sm" variant="ghost" @click="selected = []">Clear</Button>
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-[11px] font-semibold tracking-[0.1em] text-muted-foreground uppercase">
                            <th class="w-10 py-3.5 pl-5"></th>
                            <th class="px-4 py-3.5">Product</th>
                            <th class="px-4 py-3.5">Category</th>
                            <th class="px-4 py-3.5">Price</th>
                            <th class="px-4 py-3.5">Stock</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                            :class="cn('transition-colors hover:bg-muted/40', selected.includes(product.id) && 'bg-primary/5')"
                        >
                            <td class="py-3.5 pl-5">
                                <input
                                    type="checkbox"
                                    class="size-4 rounded border-muted-foreground/40 accent-primary"
                                    :checked="selected.includes(product.id)"
                                    @change="toggle(product.id)"
                                />
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="size-12 shrink-0 overflow-hidden rounded-lg border bg-secondary/40">
                                        <img
                                            v-if="product.images?.[0]?.url"
                                            :src="product.images[0].url"
                                            :alt="product.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <BottleThumb v-else :name="product.name" :category="product.category?.name" />
                                    </div>
                                    <div class="min-w-0">
                                        <Link
                                            :href="`/admin/products/${product.id}`"
                                            class="block truncate font-medium transition-colors hover:text-primary"
                                        >
                                            {{ product.name }}
                                        </Link>
                                        <p class="truncate text-xs text-muted-foreground">{{ product.brand }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-muted-foreground">{{ product.category?.name }}</td>
                            <td class="px-4 py-3.5">
                                <div class="flex flex-col leading-tight">
                                    <span class="font-medium">{{ formatMoney(product.final_price) }}</span>
                                    <span v-if="product.is_discount_active" class="text-xs text-muted-foreground line-through">
                                        {{ formatMoney(product.price) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span
                                    :class="cn(
                                        'inline-flex rounded-md px-2 py-0.5 text-xs font-medium',
                                        product.stock <= 0
                                            ? 'bg-red-500/10 text-red-600'
                                            : product.stock <= 5
                                              ? 'bg-amber-500/10 text-amber-600'
                                              : 'text-foreground',
                                    )"
                                >
                                    {{ product.stock > 0 ? `${product.stock} in stock` : 'Out of stock' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span
                                    :class="cn(
                                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
                                        product.is_active ? 'bg-emerald-500/15 text-emerald-700' : 'bg-muted text-muted-foreground',
                                    )"
                                >
                                    <span :class="cn('size-1.5 rounded-full', product.is_active ? 'bg-emerald-500' : 'bg-muted-foreground')"></span>
                                    {{ product.is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1">
                                    <Button as-child size="icon" variant="ghost">
                                        <Link :href="`/admin/products/${product.id}/edit`"><Pencil class="size-4" /></Link>
                                    </Button>
                                    <Button size="icon" variant="ghost" class="text-red-600 hover:text-red-600" @click="destroy(product)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!products.data.length">
                            <td colspan="7" class="px-5 py-12 text-center text-muted-foreground">No products found.</td>
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
