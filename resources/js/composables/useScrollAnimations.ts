/**
 * Reusable scroll-triggered reveal system.
 *
 * One consistent animation language across every page: blocks tagged
 * `data-motion` reveal on scroll; blocks inside `data-motion-group`
 * cascade with a stagger. Respects `prefers-reduced-motion` at two
 * levels — the composable bails early, and the CSS fallback zeroes
 * all durations globally.
 *
 * `refresh()` re-scans the document for `data-motion` elements that
 * were not present at mount time (deferred Inertia props). Elements
 * already tracked are never re-animated, and ScrollTrigger positions
 * are refreshed so triggers below layout changes stay accurate.
 */

import { onMounted, onUnmounted, ref } from 'vue';
import type { Ref } from 'vue';

const MOTION_DISTANCE = 32;
const MOTION_DURATION = 0.6;
const MOTION_STAGGER_CAP = 0.8;

export function useScrollAnimations(containerRef?: Ref<HTMLElement | null>) {
    const ready = ref(false);
    let mm: gsap.MatchMedia | null = null;
    let scan: () => void = () => {};
    let disposed = false;
    let pendingRefresh = false;

    const seen = new Set<HTMLElement>();

    onMounted(async () => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            ready.value = true;

            return;
        }

        const [{ gsap }, { ScrollTrigger }] = await Promise.all([
            import('gsap'),
            import('gsap/ScrollTrigger'),
        ]);

        if (disposed) {
            return;
        }

        gsap.registerPlugin(ScrollTrigger);

        const root = containerRef?.value ?? document.documentElement;
        mm = gsap.matchMedia(root);

        mm.add(
            {
                motionOK: '(prefers-reduced-motion: no-preference)',
            },
            (context) => {
                const { motionOK } = context.conditions ?? {};

                if (!motionOK) {
                    ready.value = true;

                    return;
                }

                const tweenFor = (
                    targets: gsap.TweenTarget,
                    trigger: HTMLElement,
                    stagger: number,
                    distance = MOTION_DISTANCE,
                ) =>
                    gsap.fromTo(
                        targets,
                        { y: distance, autoAlpha: 0 },
                        {
                            y: 0,
                            autoAlpha: 1,
                            duration: MOTION_DURATION,
                            ease: 'power3.out',
                            stagger,
                            scrollTrigger: {
                                trigger,
                                start: 'top 88%',
                                once: true,
                            },
                        },
                    );

                // Scans the tree for reveal elements that have not been
                // tracked yet; safe to call repeatedly as content arrives.
                scan = () => {
                    // Groups: direct children cascade together on one shared trigger.
                    gsap.utils
                        .toArray<HTMLElement>('[data-motion-group]')
                        .forEach((group) => {
                            const items = Array.from(
                                group.querySelectorAll<HTMLElement>(
                                    ':scope > [data-motion]',
                                ),
                            );
                            const fresh = items.filter((el) => !seen.has(el));

                            if (!fresh.length) {
                                return;
                            }

                            fresh.forEach((el) => seen.add(el));
                            tweenFor(
                                fresh,
                                group,
                                Math.min(
                                    0.1,
                                    MOTION_STAGGER_CAP / items.length,
                                ),
                            );
                        });

                    // Standalone blocks reveal individually.
                    gsap.utils
                        .toArray<HTMLElement>('[data-motion]')
                        .forEach((element) => {
                            if (element.closest('[data-motion-group]')) {
                                return;
                            }

                            if (seen.has(element)) {
                                return;
                            }

                            seen.add(element);
                            tweenFor(element, element, 0);
                        });

                    // Recalculate trigger positions after any layout growth.
                    ScrollTrigger.refresh();
                };

                scan();

                if (pendingRefresh) {
                    pendingRefresh = false;
                    scan();
                }

                ready.value = true;
            },
        );
    });

    onUnmounted(() => {
        disposed = true;
        mm?.revert();
        mm = null;
    });

    function refresh() {
        if (disposed) {
            return;
        }

        if (mm) {
            scan();
        } else {
            // gsap is still loading; rescan once it is ready.
            pendingRefresh = true;
        }
    }

    return { ready, refresh };
}
