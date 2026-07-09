<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Search, SlidersHorizontal } from '@lucide/vue';
import { computed, reactive } from 'vue';
import Pagination from '@/components/shop/Pagination.vue';
import ProductCard from '@/components/shop/ProductCard.vue';
import ShopLayout from '@/layouts/ShopLayout.vue';
import type { Paginated, Product } from '@/types/shop';

const props = defineProps<{
    products: Paginated<Product>;
    categories: { id: number; name: string }[];
    filters: { search: string; category_id: number | null; sort: string };
}>();

const page = usePage();
const canBuy = computed(() => !!page.props.auth.user);

const filters = reactive({
    search: props.filters.search ?? '',
    category_id: props.filters.category_id ?? '',
    sort: props.filters.sort ?? '',
});

function applyFilters(): void {
    router.get(
        '/products',
        {
            search: filters.search || undefined,
            category_id: filters.category_id || undefined,
            sort: filters.sort || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Shop all products" />
    <ShopLayout>
        <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6">
            <div class="mb-8 border-b pb-6">
                <p class="eyebrow mb-2">The cellar</p>
                <h1 class="font-display text-4xl font-semibold sm:text-5xl">All bottles</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ products.total }} labels available · delivered to your door
                </p>
            </div>

            <!-- Filter bar -->
            <div
                class="mb-6 flex flex-col gap-3 rounded-xl border bg-card p-4 shadow-sm sm:flex-row sm:items-center"
            >
                <div class="relative flex-1">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        v-model="filters.search"
                        type="search"
                        placeholder="Search products..."
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
                <select
                    v-model="filters.sort"
                    class="h-10 rounded-md border bg-background px-3 text-sm outline-none focus:border-primary"
                    @change="applyFilters"
                >
                    <option value="">Newest</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                    <option value="rating">Top rated</option>
                </select>
                <button
                    class="flex h-10 items-center justify-center gap-1.5 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    @click="applyFilters"
                >
                    <SlidersHorizontal class="size-4" /> Apply
                </button>
            </div>

            <div
                v-if="products.data.length"
                class="grid grid-cols-2 gap-x-5 gap-y-8 md:grid-cols-3 lg:grid-cols-4"
            >
                <ProductCard
                    v-for="product in products.data"
                    :key="product.id"
                    :product="product"
                    :can-buy="canBuy"
                />
            </div>
            <div
                v-else
                class="rounded-xl border border-dashed p-16 text-center text-muted-foreground"
            >
                No products match your filters.
            </div>

            <div class="mt-8">
                <Pagination :links="products.links" />
            </div>
        </div>
    </ShopLayout>
</template>
