<script setup lang="ts">
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        max?: number;
        glare?: boolean;
    }>(),
    { max: 9, glare: true },
);

const wrapper = ref<HTMLElement | null>(null);
const rx = ref(0);
const ry = ref(0);
const gx = ref(50);
const gy = ref(50);
const active = ref(false);

const innerStyle = computed(() => ({
    transform: `rotateX(${rx.value}deg) rotateY(${ry.value}deg) scale(${active.value ? 1.015 : 1})`,
}));

const glareStyle = computed(() => ({
    background: `radial-gradient(circle at ${gx.value}% ${gy.value}%, rgba(255,255,255,0.28), rgba(255,255,255,0) 55%)`,
    opacity: active.value ? '1' : '0',
}));

function onMove(event: PointerEvent): void {
    const node = wrapper.value;

    if (!node) {
        return;
    }

    const rect = node.getBoundingClientRect();
    const px = (event.clientX - rect.left) / rect.width;
    const py = (event.clientY - rect.top) / rect.height;

    ry.value = (px - 0.5) * 2 * props.max;
    rx.value = -(py - 0.5) * 2 * props.max;
    gx.value = px * 100;
    gy.value = py * 100;
    active.value = true;
}

function reset(): void {
    rx.value = 0;
    ry.value = 0;
    active.value = false;
}
</script>

<template>
    <div
        ref="wrapper"
        class="tilt-perspective"
        @pointermove="onMove"
        @pointerleave="reset"
    >
        <div class="tilt-inner" :style="innerStyle">
            <slot />
            <div
                v-if="glare"
                class="pointer-events-none absolute inset-0 rounded-[inherit] transition-opacity duration-300"
                :style="glareStyle"
            />
        </div>
    </div>
</template>

<style scoped>
.tilt-perspective {
    perspective: 1100px;
}

.tilt-inner {
    position: relative;
    transform-style: preserve-3d;
    transition:
        transform 0.3s cubic-bezier(0.22, 1, 0.36, 1),
        box-shadow 0.3s ease;
    will-change: transform;
}

@media (prefers-reduced-motion: reduce) {
    .tilt-inner {
        transform: none !important;
        transition: none;
    }
}
</style>
