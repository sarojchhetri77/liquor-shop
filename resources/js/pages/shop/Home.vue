<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Beer, GlassWater, Martini, Star, Truck, Wine } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed } from 'vue';
import ProductCard from '@/components/shop/ProductCard.vue';
import PromoModal from '@/components/shop/PromoModal.vue';
import { Button } from '@/components/ui/button';
import { vReveal } from '@/directives/reveal';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { asset } from '@/lib/asset';
import { index as products } from '@/routes/shop/products';
import type { Category, Product } from '@/types/shop';

defineProps<{
    featured: Product[];
    discounted: Product[];
    categories: Category[];
    featuredHasMore: boolean;
}>();

const page = usePage();
const canBuy = computed(() => !!page.props.auth.user);

function categoryIcon(name: string): LucideIcon {
    const key = name.toLowerCase();

    if (key.includes('beer')) {
        return Beer;
    }

    if (key.includes('wine') || key.includes('champagne')) {
        return Wine;
    }

    if (key.includes('whisk') || key.includes('tequila') || key.includes('vodka')) {
        return GlassWater;
    }

    return Martini;
}
</script>

<template>
    <Head title="PB Store — Fine Wine & Spirits, delivered" />
    <ShopLayout>
        <PromoModal />

        <!-- Hero — full-bleed cinematic -->
        <section class="relative isolate flex min-h-[90vh] items-center overflow-hidden">
            <img
                :src="asset('images/seed/hero-wide.jpg')"
                alt="A curated wall of fine spirits"
                class="animate-kenburns absolute inset-0 -z-20 h-full w-full object-cover"
            />
            <!-- Legibility overlays -->
            <div class="absolute inset-0 -z-10 bg-gradient-to-r from-neutral-950/95 via-neutral-950/75 to-neutral-950/25"></div>
            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-neutral-950/90 via-transparent to-neutral-950/50"></div>

            <div class="mx-auto w-full max-w-7xl px-4 pt-24 pb-32 sm:px-6 lg:pt-28">
                <div class="max-w-2xl text-white">
                    <p class="flex items-center gap-3 text-xs font-semibold tracking-[0.28em] text-gold uppercase">
                        <span class="h-px w-12 bg-gold/60"></span> Est. 2024 · Imadol, Lalitpur
                    </p>
                    <h1 class="mt-7 font-display text-5xl leading-[1.02] font-semibold sm:text-6xl lg:text-8xl">
                        Life's too short<br />
                        for <span class="text-gold italic">ordinary</span> drinks.
                    </h1>
                    <p class="mt-7 max-w-lg text-lg leading-relaxed text-white/80">
                        A hand-picked cellar of wines, single malts, craft beer and
                        spirits — delivered cold and discreet to your door.
                    </p>
                    <div class="mt-10 flex flex-wrap items-center gap-4">
                        <Button as-child size="lg" class="rounded-full px-8 shadow-lg">
                            <Link :href="products()" class="gap-2">
                                Explore the cellar <ArrowRight class="size-4" />
                            </Link>
                        </Button>
                        <Link
                            :href="products({ query: { sort: 'rating' } })"
                            class="inline-flex items-center rounded-full border border-white/30 px-7 py-3 text-sm font-semibold tracking-[0.14em] text-white uppercase backdrop-blur-sm transition-colors hover:bg-white/10"
                        >
                            Top rated
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Bottom stats strip -->
            <div class="absolute inset-x-0 bottom-0 border-t border-white/10 bg-neutral-950/40 backdrop-blur">
                <div
                    class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-center gap-x-10 gap-y-2 px-4 py-4 text-sm text-white/90 sm:justify-between sm:px-6"
                >
                    <span class="flex items-center gap-2">
                        <Star class="size-4 fill-gold text-gold" /> 4.8 rating · 2k+ reviews
                    </span>
                    <span class="hidden sm:inline">200+ curated labels</span>
                    <span class="flex items-center gap-2">
                        <Truck class="size-4 text-gold" /> Free delivery over Rs. 5,000
                    </span>
                    <span class="font-semibold tracking-wide text-gold uppercase">21+ · Drink responsibly</span>
                </div>
            </div>
        </section>

        <!-- Categories -->
        <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6">
            <div v-reveal class="mb-8 flex items-end justify-between">
                <div>
                    <p class="eyebrow mb-2">Browse</p>
                    <h2 class="font-display text-3xl font-semibold sm:text-4xl">Shop by category</h2>
                </div>
                <Link
                    :href="products()"
                    class="hidden text-sm font-semibold uppercase tracking-[0.14em] text-primary underline-offset-8 hover:underline sm:block"
                >
                    View all
                </Link>
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <Link
                    v-for="(category, i) in categories"
                    :key="category.id"
                    v-reveal="{ delay: i * 60 }"
                    :href="products({ query: { category_id: category.id } })"
                    class="group flex flex-col items-center gap-4 rounded-2xl border bg-card p-6 text-center transition-all duration-300 hover:-translate-y-1 hover:border-foreground/25 hover:shadow-xl"
                >
                    <span
                        class="flex size-16 items-center justify-center rounded-full bg-muted text-foreground transition-colors duration-300 group-hover:bg-foreground group-hover:text-background"
                    >
                        <component :is="categoryIcon(category.name)" class="size-7" :stroke-width="1.5" />
                    </span>
                    <div class="space-y-0.5">
                        <p class="font-display text-lg font-semibold">{{ category.name }}</p>
                        <p class="text-[11px] uppercase tracking-[0.14em] text-muted-foreground">
                            {{ category.products_count ?? 0 }}
                            {{ (category.products_count ?? 0) === 1 ? 'label' : 'labels' }}
                        </p>
                    </div>
                    <span
                        class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-muted-foreground transition-colors group-hover:text-foreground"
                    >
                        Shop <ArrowRight class="size-3.5 transition-transform group-hover:translate-x-0.5" />
                    </span>
                </Link>
            </div>
        </section>

        <!-- Editorial banner -->
        <section class="border-y bg-card">
            <div
                v-reveal
                class="mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-6 px-4 py-14 text-center sm:px-6 md:flex-row md:text-left"
            >
                <div class="max-w-xl space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gold">Cash or card on delivery</p>
                    <h2 class="font-display text-3xl font-semibold sm:text-4xl">
                        Free, discreet delivery across the valley.
                    </h2>
                    <p class="text-muted-foreground">Order before 8 PM for same-day dispatch. No advance payment required.</p>
                </div>
                <Button as-child size="lg" class="rounded-full px-7">
                    <Link :href="products()">Start your order</Link>
                </Button>
            </div>
        </section>

        <!-- Deals -->
        <section v-if="discounted.length" class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6">
            <div v-reveal class="mb-8 flex items-end justify-between">
                <div>
                    <p class="eyebrow mb-2">Limited time</p>
                    <h2 class="font-display text-3xl font-semibold sm:text-4xl">This week's offers</h2>
                </div>
                <Link :href="products()" class="flex items-center gap-1 text-sm font-semibold uppercase tracking-[0.14em] text-primary hover:underline">
                    All offers <ArrowRight class="size-4" />
                </Link>
            </div>
            <div class="grid grid-cols-2 gap-x-5 gap-y-8 md:grid-cols-4">
                <ProductCard
                    v-for="(product, i) in discounted"
                    :key="product.id"
                    v-reveal="{ delay: i * 70 }"
                    :product="product"
                    :can-buy="canBuy"
                />
            </div>
        </section>

        <!-- Featured -->
        <section class="border-t bg-card">
            <div class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6">
                <div v-reveal class="mb-8 text-center">
                    <p class="eyebrow mb-2">Customer favourites</p>
                    <h2 class="font-display text-3xl font-semibold sm:text-4xl">Top shelf selection</h2>
                </div>
                <div class="grid grid-cols-2 gap-x-5 gap-y-8 md:grid-cols-3 lg:grid-cols-4">
                    <ProductCard
                        v-for="(product, i) in featured"
                        :key="product.id"
                        v-reveal="{ delay: (i % 4) * 70 }"
                        :product="product"
                        :can-buy="canBuy"
                    />
                </div>

                <div v-if="featuredHasMore" class="mt-10 text-center">
                    <Button as-child size="lg" variant="outline" class="rounded-full px-8">
                        <Link :href="products()" class="gap-2">
                            View more products <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                </div>
            </div>
        </section>
    </ShopLayout>
</template>
