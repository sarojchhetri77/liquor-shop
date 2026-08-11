<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import Pagination from '@/components/shop/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { create as createPromotion, destroy as destroyPromotion, edit as editPromotion } from '@/routes/admin/promotions';
import type { Paginated, Promotion } from '@/types/shop';

defineProps<{
    promotions: Paginated<Promotion>;
}>();

function destroy(promotion: Promotion): void {
    if (confirm(`Delete "${promotion.title}"?`)) {
        router.delete(destroyPromotion.url(promotion.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Promotions" />
    <AdminLayout title="Promotions">
        <div class="flex items-center justify-between">
            <p class="text-sm text-muted-foreground">
                The most recent active promotion is shown as a popup on the
                storefront homepage.
            </p>
            <Button as-child>
                <Link :href="createPromotion()" class="gap-1.5"
                    ><Plus class="size-4" /> New promotion</Link
                >
            </Button>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="promotion in promotions.data"
                :key="promotion.id"
                class="overflow-hidden rounded-xl border bg-card shadow-sm"
            >
                <div class="aspect-[3/2] bg-muted">
                    <img
                        :src="promotion.image_url"
                        :alt="promotion.title"
                        class="h-full w-full object-cover"
                    />
                </div>
                <div class="flex items-start justify-between gap-2 p-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="truncate font-medium">{{ promotion.title }}</p>
                            <Badge :variant="promotion.is_active ? 'default' : 'outline'">
                                {{ promotion.is_active ? 'Active' : 'Hidden' }}
                            </Badge>
                        </div>
                        <p v-if="promotion.link" class="truncate text-xs text-muted-foreground">
                            → {{ promotion.link }}
                        </p>
                    </div>
                    <div class="flex gap-1">
                        <Button as-child size="icon" variant="ghost">
                            <Link :href="editPromotion(promotion.id)"><Pencil class="size-4" /></Link>
                        </Button>
                        <Button size="icon" variant="ghost" class="text-red-600 hover:text-red-600" @click="destroy(promotion)">
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
            <p
                v-if="!promotions.data.length"
                class="col-span-full rounded-xl border border-dashed p-10 text-center text-muted-foreground"
            >
                No promotions yet. Create one to show a popup on the homepage.
            </p>
        </div>

        <div class="mt-4">
            <Pagination :links="promotions.links" />
        </div>
    </AdminLayout>
</template>
