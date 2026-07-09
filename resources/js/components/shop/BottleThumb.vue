<script setup lang="ts">
import { Beer, GlassWater, Martini, Wine } from '@lucide/vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        name?: string;
        category?: string | null;
        label?: boolean;
    }>(),
    { name: '', category: '', label: false },
);

type Style = { gradient: string; icon: typeof Wine };

const styles: Record<string, Style> = {
    wine: { gradient: 'from-rose-900 via-red-800 to-rose-950', icon: Wine },
    red: { gradient: 'from-rose-900 via-red-800 to-rose-950', icon: Wine },
    champagne: { gradient: 'from-amber-300 via-yellow-400 to-amber-500', icon: Wine },
    sparkling: { gradient: 'from-amber-300 via-yellow-400 to-amber-500', icon: Wine },
    whiskey: { gradient: 'from-amber-700 via-amber-800 to-amber-950', icon: GlassWater },
    whisky: { gradient: 'from-amber-700 via-amber-800 to-amber-950', icon: GlassWater },
    bourbon: { gradient: 'from-amber-700 via-amber-800 to-amber-950', icon: GlassWater },
    rum: { gradient: 'from-orange-800 via-amber-800 to-orange-950', icon: GlassWater },
    tequila: { gradient: 'from-lime-600 via-amber-600 to-orange-800', icon: GlassWater },
    beer: { gradient: 'from-yellow-500 via-amber-500 to-amber-700', icon: Beer },
    vodka: { gradient: 'from-slate-300 via-slate-400 to-slate-600', icon: Martini },
    gin: { gradient: 'from-teal-500 via-emerald-600 to-slate-700', icon: Martini },
    cocktail: { gradient: 'from-fuchsia-500 via-rose-500 to-orange-500', icon: Martini },
};

const resolved = computed<Style>(() => {
    const haystack = `${props.category} ${props.name}`.toLowerCase();
    const match = Object.keys(styles).find((key) => haystack.includes(key));

    return match ? styles[match] : { gradient: 'from-primary via-rose-900 to-rose-950', icon: Wine };
});

const monogram = computed(() => (props.name || props.category || 'B').charAt(0).toUpperCase());
</script>

<template>
    <div
        :class="[
            'relative flex h-full w-full items-center justify-center overflow-hidden bg-gradient-to-br',
            resolved.gradient,
        ]"
    >
        <!-- soft light sheen -->
        <div class="absolute -left-1/4 -top-1/4 h-1/2 w-1/2 rounded-full bg-white/15 blur-2xl"></div>
        <div class="absolute right-4 top-3 text-xs font-semibold tracking-widest text-white/40">
            {{ monogram }}
        </div>
        <component :is="resolved.icon" class="size-2/5 text-white/90 drop-shadow" :stroke-width="1.25" />
        <p
            v-if="label && name"
            class="absolute inset-x-0 bottom-0 truncate bg-black/25 px-3 py-2 text-center text-sm font-medium text-white backdrop-blur-sm"
        >
            {{ name }}
        </p>
    </div>
</template>
