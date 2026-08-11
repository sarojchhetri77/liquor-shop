<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    LogOut,
    Megaphone,
    Menu,
    Package,
    ShoppingBag,
    Store,
    Tags,
    User as UserIcon,
    Users,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/sonner';
import { asset } from '@/lib/asset';
import { cn } from '@/lib/utils';
import { home, logout as logoutRoute } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as adminCategories } from '@/routes/admin/categories';
import { index as adminOrders } from '@/routes/admin/orders';
import { index as adminProducts } from '@/routes/admin/products';
import { index as adminPromotions } from '@/routes/admin/promotions';
import { index as adminStaff } from '@/routes/admin/staff';
import { edit as profile } from '@/routes/profile';

withDefaults(
    defineProps<{
        title?: string;
    }>(),
    { title: '' },
);

const page = usePage();
const auth = computed(() => page.props.auth);
const mobileOpen = ref(false);

type AdminNavItem = {
    label: string;
    href: string;
    icon: LucideIcon;
    adminOnly?: boolean;
};

const navItems: AdminNavItem[] = [
    { label: 'Dashboard', href: adminDashboard.url(), icon: LayoutDashboard },
    { label: 'Products', href: adminProducts.url(), icon: Package },
    { label: 'Categories', href: adminCategories.url(), icon: Tags },
    { label: 'Orders', href: adminOrders.url(), icon: ShoppingBag },
    { label: 'Promotions', href: adminPromotions.url(), icon: Megaphone },
    { label: 'Staff', href: adminStaff.url(), icon: Users, adminOnly: true },
];

const visibleItems = computed(() =>
    navItems.filter((item) => !item.adminOnly || auth.value.isAdmin),
);

function isActive(href: string): boolean {
    if (href === adminDashboard.url()) {
        return page.url === href;
    }

    return page.url.startsWith(href);
}

function logout(): void {
    router.post(logoutRoute.url());
}
</script>

<template>
    <div class="min-h-screen bg-muted/40 text-foreground">
        <!-- Desktop sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r bg-card md:flex"
        >
            <div class="flex h-[68px] items-center gap-2.5 border-b px-6">
                <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-md bg-white">
                    <img :src="asset('pb_store_logo.jpeg')" alt="PB Store" class="size-full object-contain" />
                </span>
                <div class="leading-none">
                    <p class="font-display text-xl font-semibold text-foreground">PB Store</p>
                    <p class="mt-0.5 text-[10px] font-semibold tracking-[0.3em] text-primary/70 uppercase">Admin</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                <p class="px-3 pb-2 text-[10px] font-semibold tracking-[0.22em] text-muted-foreground/70 uppercase">Management</p>
                <Link
                    v-for="item in visibleItems"
                    :key="item.href"
                    :href="item.href"
                    :class="
                        cn(
                            'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                            isActive(item.href)
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        )
                    "
                >
                    <component
                        :is="item.icon"
                        class="size-5"
                        :class="isActive(item.href) ? 'text-primary-foreground' : 'text-muted-foreground'"
                    />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="space-y-1 border-t p-4">
                <Link
                    :href="home()"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                >
                    <Store class="size-5" /> View store
                </Link>
                <button
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-red-600 transition-colors hover:bg-red-500/10"
                    @click="logout"
                >
                    <LogOut class="size-5" /> Log out
                </button>
            </div>
        </aside>

        <div class="md:pl-64">
            <!-- Top bar -->
            <header
                class="sticky top-0 z-20 flex h-[68px] items-center gap-3 border-b bg-background/85 px-4 backdrop-blur sm:px-6"
            >
                <Sheet v-model:open="mobileOpen">
                    <SheetTrigger
                        class="flex size-10 items-center justify-center rounded-md text-foreground hover:bg-muted md:hidden"
                    >
                        <Menu class="size-5" />
                    </SheetTrigger>
                    <SheetContent side="left" class="w-72 bg-card p-0">
                        <div class="flex h-[68px] items-center gap-2.5 border-b px-6">
                            <span class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-md bg-white">
                                <img :src="asset('pb_store_logo.jpeg')" alt="PB Store" class="size-full object-contain" />
                            </span>
                            <div class="leading-none">
                                <p class="font-display text-lg font-semibold text-foreground">PB Store</p>
                                <p class="mt-0.5 text-[10px] font-semibold tracking-[0.3em] text-primary/70 uppercase">Admin</p>
                            </div>
                        </div>
                        <nav class="space-y-1 p-4">
                            <Link
                                v-for="item in visibleItems"
                                :key="item.href"
                                :href="item.href"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                                :class="
                                    isActive(item.href)
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                "
                                @click="mobileOpen = false"
                            >
                                <component
                                    :is="item.icon"
                                    class="size-5"
                                    :class="isActive(item.href) ? 'text-primary-foreground' : 'text-muted-foreground'"
                                />
                                {{ item.label }}
                            </Link>
                        </nav>
                    </SheetContent>
                </Sheet>

                <h1 class="font-display text-xl font-semibold">{{ title }}</h1>

                <AppearanceToggle class="ml-auto" />

                <DropdownMenu>
                    <DropdownMenuTrigger
                        class="flex items-center gap-3 rounded-full py-1 pr-1 pl-3 transition-colors outline-none hover:bg-muted"
                    >
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium">{{ auth.user?.name }}</p>
                            <p class="text-xs text-muted-foreground capitalize">{{ auth.user?.role }}</p>
                        </div>
                        <span
                            class="flex size-10 items-center justify-center rounded-full bg-primary font-display text-sm font-semibold text-primary-foreground"
                        >
                            {{ auth.user?.name?.charAt(0) }}
                        </span>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <DropdownMenuLabel>
                            <div class="flex flex-col">
                                <span class="truncate font-medium">{{ auth.user?.name }}</span>
                                <span class="truncate text-xs font-normal text-muted-foreground">{{ auth.user?.email }}</span>
                            </div>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem as-child>
                            <Link :href="profile()" class="flex w-full items-center gap-2">
                                <UserIcon class="size-4" /> Account settings
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link :href="home()" class="flex w-full items-center gap-2">
                                <Store class="size-4" /> View store
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem class="gap-2 text-red-600 focus:text-red-600" @click="logout">
                            <LogOut class="size-4" /> Log out
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </header>

            <main class="mx-auto w-full max-w-7xl p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>

        <Toaster position="top-center" />
    </div>
</template>
