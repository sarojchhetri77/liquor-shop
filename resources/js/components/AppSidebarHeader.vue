<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ShoppingCart, Store } from '@lucide/vue';
import { computed } from 'vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const cartCount = computed(() => page.props.cartCount ?? 0);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Navbar actions -->
        <div class="ml-auto flex items-center gap-1">
            <AppearanceToggle />
            <Link
                href="/cart"
                class="relative flex size-10 items-center justify-center rounded-full text-foreground transition-colors hover:bg-muted"
                aria-label="Cart"
            >
                <ShoppingCart class="size-5" />
                <span
                    v-if="cartCount > 0"
                    class="absolute -top-0.5 -right-0.5 flex size-5 items-center justify-center rounded-full bg-primary text-[11px] font-semibold text-primary-foreground"
                >
                    {{ cartCount }}
                </span>
            </Link>
            <Link
                href="/"
                class="flex items-center gap-1.5 rounded-full px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            >
                <Store class="size-4" /> Store
            </Link>
        </div>
    </header>
</template>
