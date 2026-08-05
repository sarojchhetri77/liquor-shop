<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();

function toggle(): void {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <button
        type="button"
        class="flex size-10 items-center justify-center rounded-full text-foreground transition-colors hover:bg-muted"
        :aria-label="
            resolvedAppearance === 'dark'
                ? 'Switch to light mode'
                : 'Switch to dark mode'
        "
        @click="toggle"
    >
        <Sun v-if="resolvedAppearance === 'dark'" class="size-5" />
        <Moon v-else class="size-5" />
    </button>
</template>
