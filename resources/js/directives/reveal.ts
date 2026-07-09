import type { Directive } from 'vue';

type RevealBinding = { delay?: number } | undefined;

/**
 * `v-reveal` — fades and slides an element into view the first time it enters
 * the viewport. Pass an optional stagger delay: `v-reveal="{ delay: 80 }"`.
 *
 * Pairs with the `.reveal` / `.reveal-in` styles in app.css.
 */
export const vReveal: Directive<HTMLElement, RevealBinding> = {
    mounted(el, binding) {
        el.classList.add('reveal');

        const delay = binding.value?.delay ?? 0;

        if (delay) {
            el.style.transitionDelay = `${delay}ms`;
        }

        // Fallback: if observers aren't available, just show the element.
        if (typeof IntersectionObserver === 'undefined') {
            el.classList.add('reveal-in');

            return;
        }

        const observer = new IntersectionObserver(
            (entries, obs) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        el.classList.add('reveal-in');
                        obs.unobserve(el);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -8% 0px' },
        );

        observer.observe(el);
    },
};
