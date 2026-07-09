<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types/shop';

defineProps<{
    links: PaginationLink[];
}>();
</script>

<template>
    <nav
        v-if="links.length > 3"
        class="flex flex-wrap items-center justify-center gap-1"
    >
        <template v-for="(link, index) in links" :key="index">
            <span
                v-if="!link.url"
                class="rounded-md px-3 py-2 text-sm text-muted-foreground/50"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                preserve-scroll
                :class="
                    cn(
                        'rounded-md px-3 py-2 text-sm transition-colors',
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'text-foreground hover:bg-muted',
                    )
                "
            >
                <span v-html="link.label" />
            </Link>
        </template>
    </nav>
</template>
