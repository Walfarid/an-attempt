/**
 * initRouteTransition — subtle fade-and-rise on SPA page visits.
 *
 * At `inertia:start` the app root is dismissed (the top drafting line
 * stays visible — it lives outside the root), and at `inertia:finish`
 * the new page rises in. The landing page is exempt: it owns its own
 * hero choreography, and a global fade would fight it.
 *
 * Prefetch visits are ignored, and the whole effect is disabled under
 * `prefers-reduced-motion` (content stays visible, no flash).
 *
 * Deferred-props and partial reloads are background syncs: the current
 * page must stay visible while they run, so they are ignored too —
 * hiding the root there would blank the page until the request lands.
 */
import { gsap } from 'gsap';

const REVEAL_DURATION = 0.28;
const REVEAL_DISTANCE = 8;

export function initRouteTransition(root: HTMLElement | null) {
    if (!root) {
        return;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    let active = false;

    const onStart = (event: Event) => {
        const visit = (event as CustomEvent).detail?.visit as
            | {
                  prefetch?: boolean;
                  deferredProps?: boolean;
                  only?: string[] | null;
              }
            | undefined;

        // Keep the page visible during background syncs (prefetch,
        // deferred props, partial reloads) — only real navigations fade.
        if (
            visit?.prefetch === true ||
            visit?.deferredProps === true ||
            visit?.only?.length
        ) {
            return;
        }

        active = true;
        gsap.set(root, { autoAlpha: 0, y: REVEAL_DISTANCE });
    };

    const onFinish = (event: Event) => {
        if (!active) {
            return;
        }

        active = false;

        // The landing page plays its own entrance; restore it instantly.
        // Inertia v3 exposes visit.url as a URL object, not a string.
        const visit = (event as CustomEvent).detail?.visit as
            { url?: string | URL } | undefined;
        const rawUrl = visit?.url;
        const path =
            rawUrl instanceof URL
                ? rawUrl.pathname
                : (rawUrl ?? '').split('?')[0];

        if (path === '/') {
            gsap.set(root, { autoAlpha: 1, y: 0 });

            return;
        }

        gsap.to(root, {
            autoAlpha: 1,
            y: 0,
            duration: REVEAL_DURATION,
            ease: 'power2.out',
            overwrite: true,
        });
    };

    const onError = () => {
        if (!active) {
            return;
        }

        active = false;
        gsap.set(root, { autoAlpha: 1, y: 0 });
    };

    // Listeners live for the lifetime of the app — deliberate.
    document.addEventListener('inertia:start', onStart);
    document.addEventListener('inertia:finish', onFinish);
    document.addEventListener('inertia:error', onError);
}
