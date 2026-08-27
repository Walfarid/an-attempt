/**
 * useCountUp — one-shot number count-ups for stat displays.
 *
 * Elements flagged with `data-count-to="N"` count up once when they
 * scroll into view. The markup ships the final value and it is zeroed
 * only when the animation actually runs, so reduced-motion users and
 * JS-failure states still see the real number.
 */
import { onMounted, onUnmounted } from 'vue';
import type { Ref } from 'vue';

const COUNT_DURATION = 1.2;

export function useCountUp(containerRef: Ref<HTMLElement | null>) {
    let mm: gsap.MatchMedia | null = null;
    let disposed = false;

    onMounted(async () => {
        const [{ gsap }, { ScrollTrigger }] = await Promise.all([
            import('gsap'),
            import('gsap/ScrollTrigger'),
        ]);

        if (disposed) {
            return;
        }

        gsap.registerPlugin(ScrollTrigger);

        const root = containerRef.value ?? document.documentElement;

        mm = gsap.matchMedia(root);

        mm.add('(prefers-reduced-motion: no-preference)', () => {
            const counters = Array.from(
                root.querySelectorAll<HTMLElement>('[data-count-to]'),
            );

            counters.forEach((element) => {
                const target = Number(element.dataset.countTo);

                if (!Number.isFinite(target)) {
                    return;
                }

                // Zero at setup; the tween writes every intermediate value.
                element.textContent = '0';

                const state = { value: 0 };

                gsap.to(state, {
                    value: target,
                    duration: COUNT_DURATION,
                    ease: 'power2.out',
                    scrollTrigger: {
                        trigger: element,
                        start: 'top 92%',
                        once: true,
                    },
                    onUpdate: () => {
                        element.textContent = String(Math.round(state.value));
                    },
                });
            });
        });
    });

    onUnmounted(() => {
        disposed = true;
        mm?.revert();
        mm = null;
    });
}
