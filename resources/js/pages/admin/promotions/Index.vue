<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ImageOff, Pencil, Plus, Trash2 } from '@lucide/vue';
import Pagination from '@/components/shop/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    create as createPromotion,
    destroy as destroyPromotion,
    edit as editPromotion,
} from '@/routes/admin/promotions';
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
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-muted-foreground">
                Active promotions are shown as a slider in the storefront popup, in
                display order. Hidden ones stay here without appearing to customers.
            </p>
            <Button as-child class="h-11 shrink-0">
                <Link :href="createPromotion()" class="gap-1.5">
                    <Plus class="size-4" /> New promotion
                </Link>
            </Button>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-[11px] font-semibold tracking-[0.1em] text-muted-foreground uppercase">
                            <th class="px-5 py-3.5">Promotion</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="hidden px-5 py-3.5 md:table-cell">Order</th>
                            <th class="hidden px-5 py-3.5 lg:table-cell">Link</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="promotion in promotions.data"
                            :key="promotion.id"
                            class="transition-colors hover:bg-muted/40"
                        >
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="h-11 w-16 shrink-0 overflow-hidden rounded-lg border bg-secondary/40">
                                        <img
                                            v-if="promotion.image_url"
                                            :src="promotion.image_url"
                                            :alt="promotion.title"
                                            class="h-full w-full object-cover"
                                        />
                                        <div v-else class="flex h-full w-full items-center justify-center text-muted-foreground">
                                            <ImageOff class="size-4" />
                                        </div>
                                    </div>
                                    <Link
                                        :href="editPromotion(promotion.id)"
                                        class="min-w-0 truncate font-medium transition-colors hover:text-primary"
                                    >
                                        {{ promotion.title }}
                                    </Link>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <Badge :variant="promotion.is_active ? 'default' : 'outline'">
                                    {{ promotion.is_active ? 'Active' : 'Hidden' }}
                                </Badge>
                            </td>
                            <td class="hidden px-5 py-3.5 text-muted-foreground md:table-cell">
                                {{ promotion.sort_order }}
                            </td>
                            <td class="hidden max-w-xs px-5 py-3.5 text-muted-foreground lg:table-cell">
                                <span class="block truncate">{{ promotion.link || '—' }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1">
                                    <Button as-child size="icon" variant="ghost">
                                        <Link :href="editPromotion(promotion.id)"><Pencil class="size-4" /></Link>
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        class="text-red-600 hover:text-red-600"
                                        @click="destroy(promotion)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!promotions.data.length">
                            <td colspan="5" class="px-5 py-12 text-center text-muted-foreground">
                                No promotions yet. Create one to show a popup on the homepage.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <Pagination :links="promotions.links" />
        </div>
    </AdminLayout>
</template>
