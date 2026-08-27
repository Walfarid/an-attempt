/**
 * useHeroScene — landing hero entrance and one faint pointer parallax.
 *
 * Entrance: the name is revealed through a clip-path mask, the tagline
 * and CTA rise into place, and the constellation draws itself — rings
 * and links trace in, nodes and dots pop, accent marks settle. The
 * sequence runs exactly once.
 *
 * The markup does not pre-hide anything, so reduced-motion users and
 * no-JS users see the hero immediately; this composable only adds
 * motion when the user has not opted out (WCAG 2.3.3).
 *
 * Pointer depth: a single faint parallax layer on the illustration,
 * gated to wide screens with fine pointers and motion OK.
 */
import { onMounted, onUnmounted } from 'vue';
import type { Ref } from 'vue';

export function useHeroScene(containerRef: Ref<HTMLElement | null>) {
    let mm: gsap.MatchMedia | null = null;
    let removePointerListeners: (() => void) | null = null;
    let disposed = false;

    onMounted(async () => {
        const [{ gsap }] = await Promise.all([import('gsap')]);

        if (disposed) {
            return;
        }

        const root = containerRef.value ?? document.documentElement;

        mm = gsap.matchMedia(root);

        mm.add(
            {
                motionOK: '(prefers-reduced-motion: no-preference)',
                wide: '(min-width: 1024px)',
                finePointer: '(pointer: fine)',
            },
            (context) => {
                const { motionOK, wide, finePointer } =
                    context.conditions ?? {};

                if (!motionOK) {
                    return;
                }

                const rings = Array.from(
                    root.querySelectorAll<SVGCircleElement>('.c-rings circle'),
                );
                const lines = Array.from(
                    root.querySelectorAll<SVGLineElement>('.c-lines line'),
                );
                const nodes = Array.from(
                    root.querySelectorAll<SVGCircleElement>('.c-nodes circle'),
                );
                const dots = Array.from(
                    root.querySelectorAll<SVGCircleElement>('.c-dots circle'),
                );
                const marks = Array.from(
                    root.querySelectorAll<SVGRectElement>('.c-marks rect'),
                );

                // Trace-in for every stroked path: measure once, set the
                // start state, then let the timeline drive them to zero.
                const strokeTargets = [...rings, ...lines];

                strokeTargets.forEach((element) => {
                    const length = element.getTotalLength();

                    gsap.set(element, {
                        strokeDasharray: length,
                        strokeDashoffset: length,
                    });
                });

                const sequence = gsap.timeline({
                    defaults: { ease: 'power3.out' },
                });

                sequence
                    .fromTo(
                        '.hero-name',
                        { y: 40, clipPath: 'inset(0% 0% 100% 0%)' },
                        { y: 0, clipPath: 'inset(0% 0% 0% 0%)', duration: 0.9 },
                    )
                    .fromTo(
                        '.hero-tagline',
                        { y: 24, autoAlpha: 0 },
                        { y: 0, autoAlpha: 1, duration: 0.6 },
                        '-=0.55',
                    )
                    .fromTo(
                        '.hero-cta',
                        { y: 16, autoAlpha: 0 },
                        { y: 0, autoAlpha: 1, duration: 0.45 },
                        '-=0.3',
                    )
                    .to(
                        strokeTargets,
                        {
                            strokeDashoffset: 0,
                            duration: 0.9,
                            stagger: 0.06,
                            ease: 'power2.inOut',
                        },
                        '-=0.45',
                    )
                    .fromTo(
                        nodes,
                        { scale: 0, autoAlpha: 0, transformOrigin: 'center' },
                        {
                            scale: 1,
                            autoAlpha: 1,
                            duration: 0.5,
                            stagger: 0.05,
                            ease: 'back.out(1.6)',
                        },
                        '-=0.65',
                    )
                    .fromTo(
                        dots,
                        { scale: 0, autoAlpha: 0, transformOrigin: 'center' },
                        {
                            scale: 1,
                            autoAlpha: 1,
                            duration: 0.4,
                            stagger: 0.04,
                            ease: 'back.out(1.6)',
                        },
                        '-=0.4',
                    )
                    .fromTo(
                        marks,
                        {
                            scale: 0.6,
                            autoAlpha: 0,
                            rotation: -10,
                            transformOrigin: 'center',
                        },
                        {
                            scale: 1,
                            autoAlpha: 1,
                            rotation: 0,
                            duration: 0.4,
                            stagger: 0.05,
                        },
                        '-=0.4',
                    );

                // One faint parallax layer on the illustration.
                const scene = root.querySelector<HTMLElement>('.hero-scene');

                if (!scene || !wide || !finePointer) {
                    return;
                }

                // Re-running callbacks (media changes) re-create the
                // quickTo tweens, so drop stale listeners first.
                removePointerListeners?.();

                const xTo = gsap.quickTo(scene, 'x', {
                    duration: 0.7,
                    ease: 'power3.out',
                });
                const yTo = gsap.quickTo(scene, 'y', {
                    duration: 0.7,
                    ease: 'power3.out',
                });
                const rotationTo = gsap.quickTo(scene, 'rotation', {
                    duration: 0.7,
                    ease: 'power3.out',
                });

                const onPointerMove = (event: MouseEvent) => {
                    const nx = event.clientX / window.innerWidth - 0.5;
                    const ny = event.clientY / window.innerHeight - 0.5;

                    xTo(nx * 14);
                    yTo(ny * 10);
                    rotationTo(nx * 1.2);
                };

                const onPointerLeave = () => {
                    xTo(0);
                    yTo(0);
                    rotationTo(0);
                };

                window.addEventListener('mousemove', onPointerMove, {
                    passive: true,
                });
                document.addEventListener('mouseleave', onPointerLeave);

                removePointerListeners = () => {
                    window.removeEventListener('mousemove', onPointerMove);
                    document.removeEventListener('mouseleave', onPointerLeave);
                    removePointerListeners = null;
                };
            },
        );
    });

    onUnmounted(() => {
        disposed = true;
        removePointerListeners?.();
        mm?.revert();
        mm = null;
    });
}
