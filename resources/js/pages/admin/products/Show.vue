<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink, Pencil, Star, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import BottleThumb from '@/components/shop/BottleThumb.vue';
import StarRating from '@/components/shop/StarRating.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Product } from '@/types/shop';

const props = defineProps<{
    product: Product;
}>();

const activeImage = ref(props.product.images?.[0]?.url ?? null);

const dateFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' });

function formatDateTime(value: string | null): string {
    return value ? dateFormatter.format(new Date(value)) : '—';
}

/** Human-readable status for a configured discount: active / scheduled / expired. */
const discountStatus = computed<{ label: string; classes: string } | null>(() => {
    if (props.product.discount_percent <= 0) {
        return null;
    }

    if (props.product.is_discount_active) {
        return { label: 'Active', classes: 'bg-emerald-500/15 text-emerald-700' };
    }

    const startsAt = props.product.discount_starts_at;

    if (startsAt && new Date(startsAt) > new Date()) {
        return { label: 'Scheduled', classes: 'bg-amber-500/15 text-amber-700' };
    }

    return { label: 'Expired', classes: 'bg-muted text-muted-foreground' };
});

function destroy(): void {
    if (confirm(`Delete "${props.product.name}"? This cannot be undone.`)) {
        router.delete(`/admin/products/${props.product.id}`);
    }
}
</script>

<template>
    <Head :title="product.name" />
    <AdminLayout :title="product.name">
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <Link
                href="/admin/products"
                class="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4" /> Back to products
            </Link>
            <div class="flex items-center gap-2">
                <Button as-child variant="outline">
                    <Link :href="`/products/${product.slug}`" class="gap-1.5"><ExternalLink class="size-4" /> View in store</Link>
                </Button>
                <Button as-child>
                    <Link :href="`/admin/products/${product.id}/edit`" class="gap-1.5"><Pencil class="size-4" /> Edit</Link>
                </Button>
                <Button variant="ghost" size="icon" class="text-red-600 hover:text-red-600" @click="destroy">
                    <Trash2 class="size-4" />
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <!-- Gallery -->
            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                    <div class="aspect-square bg-secondary/40">
                        <img v-if="activeImage" :src="activeImage" :alt="product.name" class="h-full w-full object-cover" />
                        <BottleThumb v-else :name="product.name" :category="product.category?.name" />
                    </div>
                    <div v-if="product.images && product.images.length > 1" class="flex flex-wrap gap-2 p-3">
                        <button
                            v-for="image in product.images"
                            :key="image.id"
                            class="size-16 overflow-hidden rounded-lg border-2 transition-colors"
                            :class="activeImage === image.url ? 'border-primary' : 'border-transparent'"
                            @click="activeImage = image.url"
                        >
                            <img :src="image.url" class="h-full w-full object-cover" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="space-y-6 lg:col-span-3">
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p v-if="product.brand" class="text-xs font-semibold tracking-[0.14em] text-primary/70 uppercase">{{ product.brand }}</p>
                            <h1 class="mt-1 font-display text-2xl font-semibold">{{ product.name }}</h1>
                        </div>
                        <span
                            :class="cn(
                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
                                product.is_active ? 'bg-emerald-500/15 text-emerald-700' : 'bg-muted text-muted-foreground',
                            )"
                        >
                            <span :class="cn('size-1.5 rounded-full', product.is_active ? 'bg-emerald-500' : 'bg-muted-foreground')"></span>
                            {{ product.is_active ? 'Active' : 'Hidden' }}
                        </span>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <StarRating :rating="Number(product.rating)" :size="16" />
                        <span class="text-sm text-muted-foreground">{{ Number(product.rating).toFixed(1) }} · {{ product.reviews_count }} reviews</span>
                    </div>

                    <div class="mt-4 flex items-end gap-3">
                        <span class="font-display text-3xl font-semibold">{{ formatMoney(product.final_price) }}</span>
                        <template v-if="product.is_discount_active">
                            <span class="text-lg text-muted-foreground line-through">{{ formatMoney(product.price) }}</span>
                            <span class="rounded-full bg-gold/15 px-2 py-0.5 text-sm font-medium text-amber-700">-{{ product.effective_discount_percent }}%</span>
                        </template>
                    </div>

                    <div v-if="discountStatus" class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                        <span :class="cn('inline-flex items-center rounded-full px-2 py-0.5 font-medium', discountStatus.classes)">
                            {{ product.discount_percent }}% discount · {{ discountStatus.label }}
                        </span>
                        <span v-if="product.discount_starts_at">From {{ formatDateTime(product.discount_starts_at) }}</span>
                        <span v-if="product.discount_ends_at">Until {{ formatDateTime(product.discount_ends_at) }}</span>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-4 border-t pt-5 text-sm sm:grid-cols-3">
                        <div>
                            <p class="text-xs tracking-wide text-muted-foreground uppercase">Category</p>
                            <p class="mt-0.5 font-medium">{{ product.category?.name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs tracking-wide text-muted-foreground uppercase">Stock</p>
                            <p class="mt-0.5 font-medium" :class="product.stock <= 0 ? 'text-red-600' : product.stock <= 5 ? 'text-amber-600' : ''">
                                {{ product.stock }} units
                            </p>
                        </div>
                        <div>
                            <p class="text-xs tracking-wide text-muted-foreground uppercase">Slug</p>
                            <p class="mt-0.5 truncate font-medium">{{ product.slug }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="product.description" class="rounded-2xl border bg-card p-6 shadow-sm">
                    <h2 class="mb-2 font-display text-lg font-semibold">Description</h2>
                    <p class="text-sm whitespace-pre-line text-muted-foreground">{{ product.description }}</p>
                </div>

                <!-- Reviews -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 font-display text-lg font-semibold">
                        Reviews <span class="text-muted-foreground">({{ product.reviews_count }})</span>
                    </h2>
                    <div v-if="product.reviews && product.reviews.length" class="space-y-4">
                        <div v-for="review in product.reviews" :key="review.id" class="border-b pb-4 last:border-0 last:pb-0">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="flex size-8 items-center justify-center rounded-full bg-primary/10 font-display text-sm font-semibold text-primary">
                                        {{ review.user?.name?.charAt(0) ?? '?' }}
                                    </span>
                                    <span class="text-sm font-medium">{{ review.user?.name ?? 'Customer' }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-amber-500">
                                    <Star class="size-3.5 fill-current" />
                                    <span class="text-sm font-medium text-foreground">{{ review.rating }}</span>
                                </div>
                            </div>
                            <p v-if="review.comment" class="mt-2 text-sm text-muted-foreground">{{ review.comment }}</p>
                        </div>
                    </div>
                    <p v-else class="py-6 text-center text-sm text-muted-foreground">No reviews yet.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
