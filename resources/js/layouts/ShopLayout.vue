<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Clock,
    LayoutDashboard,
    LogOut,
    Mail,
    MapPin,
    Package,
    Phone,
    ShoppingBag,
    ShoppingCart,
    Truck,
    User as UserIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import FacebookIcon from '@/components/icons/FacebookIcon.vue';
import InstagramIcon from '@/components/icons/InstagramIcon.vue';
import TikTokIcon from '@/components/icons/TikTokIcon.vue';
import SearchSuggestions from '@/components/shop/SearchSuggestions.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import { asset } from '@/lib/asset';
import { dashboard, home, login, logout as logoutRoute, register } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { edit as profile } from '@/routes/profile';
import { index as cart } from '@/routes/shop/cart';
import { index as orders } from '@/routes/shop/orders';
import { index as products } from '@/routes/shop/products';

const page = usePage();

const auth = computed(() => page.props.auth);
const cartCount = computed(() => page.props.cartCount ?? 0);
const isAuthenticated = computed(() => !!auth.value.user);
const categories = computed(() => page.props.navCategories ?? []);
const year = new Date().getFullYear();

function logout(): void {
    router.post(logoutRoute.url());
}

const navLinks = [
    { label: 'Home', href: home.url() },
    { label: 'Shop', href: products.url() },
];

const socials = [
    { label: 'Instagram', icon: InstagramIcon, href: '#' },
    { label: 'Facebook', icon: FacebookIcon, href: '#' },
    { label: 'TikTok', icon: TikTokIcon, href: '#' },
];
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Announcement bar -->
        <div class="border-b bg-card text-foreground">
            <div
                class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-center gap-x-6 gap-y-1 px-4 py-2 text-[11px] uppercase tracking-[0.16em] sm:justify-between sm:px-6"
            >
                <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
                    <span class="flex items-center gap-1.5">
                        <Phone class="size-3.5 text-gold" /> Order by phone
                        <a href="tel:+9779841307715" class="font-semibold hover:underline">9841307715</a>
                    </span>
                    <span class="hidden items-center gap-1.5 sm:flex">
                        <Clock class="size-3.5 text-gold" /> 11:00 – 21:30 (NST)
                    </span>
                </div>
                <span class="flex items-center gap-1.5">
                    <Truck class="size-3.5 text-gold" /> Cash or card on delivery
                </span>
            </div>
        </div>

        <header
            class="sticky top-0 z-40 border-b bg-background/85 backdrop-blur"
        >
            <div
                class="mx-auto flex h-[68px] w-full max-w-7xl items-center gap-5 px-4 sm:px-6"
            >
                <Link :href="home()" class="flex items-center gap-2.5">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-md bg-white"
                    >
                        <img :src="asset('pb_store_logo.jpeg')" alt="PB Store" class="size-full object-contain" />
                    </span>
                    <span class="hidden flex-col leading-none sm:flex">
                        <span class="font-display text-2xl font-semibold tracking-tight">
                            PB Store<span class="text-gold">.</span>
                        </span>
                        <span class="text-[9px] font-medium uppercase tracking-[0.3em] text-muted-foreground">
                            Fine Wine &amp; Spirits
                        </span>
                    </span>
                </Link>

                <nav class="hidden items-center gap-6 md:flex">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground transition-colors hover:text-primary"
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <SearchSuggestions class="ml-auto hidden max-w-xs flex-1 sm:block" />

                <div class="ml-auto flex items-center gap-1 sm:ml-0">
                    <AppearanceToggle />
                    <Link
                        :href="cart()"
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

                    <template v-if="isAuthenticated">
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                class="flex size-10 items-center justify-center rounded-full text-foreground transition-colors hover:bg-muted"
                            >
                                <UserIcon class="size-5" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-52">
                                <DropdownMenuLabel>
                                    <div class="flex flex-col">
                                        <span class="truncate font-medium">{{
                                            auth.user?.name
                                        }}</span>
                                        <span
                                            class="truncate text-xs text-muted-foreground"
                                            >{{ auth.user?.email }}</span
                                        >
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem as-child>
                                    <Link
                                        :href="dashboard()"
                                        class="flex w-full items-center gap-2"
                                    >
                                        <LayoutDashboard class="size-4" /> Dashboard
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link
                                        :href="orders()"
                                        class="flex w-full items-center gap-2"
                                    >
                                        <Package class="size-4" /> My Orders
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link
                                        :href="profile()"
                                        class="flex w-full items-center gap-2"
                                    >
                                        <UserIcon class="size-4" /> Account
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-if="auth.canAccessAdminPanel"
                                    as-child
                                >
                                    <Link
                                        :href="adminDashboard()"
                                        class="flex w-full items-center gap-2"
                                    >
                                        <ShoppingBag class="size-4" /> Admin
                                        Panel
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    class="gap-2 text-red-600 focus:text-red-600"
                                    @click="logout"
                                >
                                    <LogOut class="size-4" /> Log out
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md px-3 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="hidden rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 sm:inline-block"
                        >
                            Sign up
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="mt-10 border-t bg-muted/40">
            <div class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6">
                <div class="grid gap-10 lg:grid-cols-4 lg:gap-8">
                    <!-- Brand + contact -->
                    <div class="space-y-4 lg:col-span-1">
                        <Link :href="home()" class="flex items-center gap-2.5">
                            <span
                                class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-md bg-white"
                            >
                                <img :src="asset('pb_store_logo.jpeg')" alt="PB Store" class="size-full object-contain" />
                            </span>
                            <span class="font-display text-2xl font-semibold">
                                PB Store<span class="text-gold">.</span>
                            </span>
                        </Link>
                        <p class="text-sm text-muted-foreground">
                            Nepal's cellar for hand-picked wines, premium spirits
                            and ice-cold beer — delivered to your door.
                        </p>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li class="flex items-center gap-2">
                                <Phone class="size-4 text-primary" />
                                <a href="tel:+9779841307715" class="hover:text-foreground">9841307715</a>
                            </li>
                            <li class="flex items-center gap-2">
                                <Mail class="size-4 text-primary" />
                                <a href="mailto:hello@nectar.test" class="hover:text-foreground">hello@nectar.test</a>
                            </li>
                            <li class="flex items-center gap-2">
                                <Clock class="size-4 text-primary" />
                                11:00 AM – 09:30 PM (NST)
                            </li>
                            <li class="flex items-center gap-2">
                                <MapPin class="size-4 text-primary" />
                                Imadol, Lalitpur
                            </li>
                        </ul>
                    </div>

                    <!-- Shop categories -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold tracking-wide uppercase">Shop</h3>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li v-for="category in categories" :key="category.id">
                                <Link
                                    :href="products({ query: { category_id: category.id } })"
                                    class="transition-colors hover:text-foreground"
                                >
                                    {{ category.name }}
                                </Link>
                            </li>
                            <li>
                                <Link :href="products()" class="font-medium text-primary hover:underline">
                                    View all
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Account / help -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold tracking-wide uppercase">My account</h3>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li><Link :href="orders()" class="hover:text-foreground">My orders</Link></li>
                            <li><Link :href="cart()" class="hover:text-foreground">My cart</Link></li>
                            <li v-if="!isAuthenticated"><Link :href="login()" class="hover:text-foreground">Sign in</Link></li>
                            <li v-if="!isAuthenticated"><Link :href="register()" class="hover:text-foreground">Create account</Link></li>
                            <li><Link :href="profile()" v-if="isAuthenticated" class="hover:text-foreground">Profile</Link></li>
                            <li><a href="tel:+9779841307715" class="hover:text-foreground">Help &amp; support</a></li>
                        </ul>
                    </div>

                    <!-- Delivery + social -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold tracking-wide uppercase">Delivery &amp; payment</h3>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li class="flex items-center gap-2">
                                <Truck class="size-4 text-primary" /> Cash or card on delivery
                            </li>
                            <li>Free delivery over Rs. 5,000</li>
                            <li>Same-day dispatch before 8 PM</li>
                        </ul>
                        <div>
                            <p class="mb-2 text-sm font-medium">Follow us</p>
                            <div class="flex gap-2">
                                <a
                                    v-for="social in socials"
                                    :key="social.label"
                                    :href="social.href"
                                    :aria-label="social.label"
                                    class="flex size-9 items-center justify-center rounded-full border text-muted-foreground transition-colors hover:border-primary hover:bg-primary hover:text-primary-foreground"
                                >
                                    <component :is="social.icon" class="size-4" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-10 flex flex-col items-center justify-between gap-3 border-t pt-6 text-xs text-muted-foreground sm:flex-row"
                >
                    <p>&copy; {{ year }} PB Store. All rights reserved.</p>
                    <p class="font-semibold tracking-wide text-primary uppercase">
                        You must be 21+ to purchase · Please drink responsibly
                    </p>
                </div>
            </div>
        </footer>

        <Toaster position="top-center" />
    </div>
</template>
