<script setup lang="ts">
import { Star } from '@lucide/vue';
import { computed, ref } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue?: number;
        rating?: number;
        readonly?: boolean;
        size?: number;
    }>(),
    {
        modelValue: 0,
        rating: 0,
        readonly: true,
        size: 16,
    },
);

const emit = defineEmits<{ 'update:modelValue': [value: number] }>();

const hovered = ref(0);

const displayValue = computed(() =>
    props.readonly ? props.rating : hovered.value || props.modelValue,
);

function isFilled(index: number): boolean {
    return index <= Math.round(displayValue.value);
}

function select(index: number): void {
    if (props.readonly) {
        return;
    }

    emit('update:modelValue', index);
}
</script>

<template>
    <div
        class="flex items-center gap-0.5"
        :class="{ 'cursor-pointer': !readonly }"
    >
        <button
            v-for="index in 5"
            :key="index"
            type="button"
            :disabled="readonly"
            class="transition-transform"
            :class="{
                'hover:scale-110': !readonly,
                'cursor-default': readonly,
            }"
            @mouseenter="!readonly && (hovered = index)"
            @mouseleave="!readonly && (hovered = 0)"
            @click="select(index)"
        >
            <Star
                :size="size"
                :class="
                    cn(
                        'transition-colors',
                        isFilled(index)
                            ? 'fill-amber-400 text-amber-400'
                            : 'fill-transparent text-muted-foreground/40',
                    )
                "
            />
        </button>
    </div>
</template>
