<script setup lang="ts">
/**
 * PageDrawLoader — the destination page draws itself into existence.
 *
 * Boot: the page's own dot-grid texture covers the screen; when Inertia
 * finishes mounting (`page-loader:boot-complete`), the real page layout
 * is measured and drawn as SVG strokes that resolve into the live page
 * (a small "Drafting…" caption keeps the boot legible). The full
 * overlay is boot-only.
 *
 * SPA: `inertia:start` / `inertia:finish` drive a lightweight top
 * "drafting line" bar tied to real progress.
 *
 * Animation is dependency-free: the top bar uses CSS transitions
 * (same durations/easings gsap used — power2.out = cubic-bezier
 * (0.25, 0.46, 0.45, 0.94), power2.inOut = cubic-bezier(0.455, 0.03,
 * 0.515, 0.955)) and the boot drawing uses the Web Animations API with
 * the identical timeline. Degrades to a static reveal when
 * prefers-reduced-motion is set, and a 3s hard cap in the composable
 * guarantees the page is never blocked.
 */
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePageLoader } from '@/composables/usePageLoader';

const { progress, isSpaActive, isBoot } = usePageLoader();

const overlay = ref<HTMLDivElement | null>(null);
const svgHost = ref<SVGSVGElement | null>(null);

// Live WAAPI animations, cancelled on unmount (replaces the gsap timeline).
const animations = new Set<Animation>();

// Reduced-motion detection (synchronous initialization)
const prefersReducedMotion = ref(false);
let reducedMotionMql: MediaQueryList | null = null;
let reducedMotionHandler: ((e: MediaQueryListEvent) => void) | null = null;

if (typeof window !== 'undefined') {
    reducedMotionMql = window.matchMedia('(prefers-reduced-motion: reduce)');
    prefersReducedMotion.value = reducedMotionMql.matches;
    reducedMotionHandler = (e) => {
        prefersReducedMotion.value = e.matches;
    };
    reducedMotionMql.addEventListener('change', reducedMotionHandler);
}

// --------------------------------------------------------- drawing
function measurePage(): Array<{
    x: number;
    y: number;
    width: number;
    height: number;
}> {
    const regions: Array<{
        x: number;
        y: number;
        width: number;
        height: number;
    }> = [];

    // Measure semantic landmarks
    const landmarks = ['header', 'main', 'footer', 'nav'];
    landmarks.forEach((tag) => {
        const els = document.querySelectorAll(tag);
        els.forEach((el) => {
            const rect = el.getBoundingClientRect();

            if (rect.width > 0 && rect.height > 0) {
                regions.push({
                    x: rect.left,
                    y: rect.top,
                    width: rect.width,
                    height: rect.height,
                });
            }
        });
    });

    // Also measure direct children of main
    const main = document.querySelector('main');

    if (main) {
        const children = main.children;

        for (let i = 0; i < children.length; i++) {
            const child = children[i];
            const rect = child.getBoundingClientRect();

            if (rect.width > 0 && rect.height > 0) {
                const isDuplicate = regions.some(
                    (r) =>
                        Math.abs(r.x - rect.left) < 2 &&
                        Math.abs(r.y - rect.top) < 2 &&
                        Math.abs(r.width - rect.width) < 2 &&
                        Math.abs(r.height - rect.height) < 2,
                );

                if (!isDuplicate) {
                    regions.push({
                        x: rect.left,
                        y: rect.top,
                        width: rect.width,
                        height: rect.height,
                    });
                }
            }
        }
    }

    return regions;
}

function drawRegions(
    regions: Array<{ x: number; y: number; width: number; height: number }>,
): Array<{ rect: SVGRectElement; length: number }> {
    if (!svgHost.value) {
        return [];
    }

    const svg = svgHost.value;

    // Clear previous rects
    while (svg.firstChild) {
        svg.removeChild(svg.firstChild);
    }

    return regions.map((r) => {
        const rect = document.createElementNS(
            'http://www.w3.org/2000/svg',
            'rect',
        );
        rect.setAttribute('x', r.x.toString());
        rect.setAttribute('y', r.y.toString());
        rect.setAttribute('width', r.width.toString());
        rect.setAttribute('height', r.height.toString());
        rect.setAttribute('fill', 'none');
        rect.setAttribute('stroke', 'var(--accent)');
        rect.setAttribute('stroke-width', '2');
        rect.setAttribute('rx', '4');
        svg.appendChild(rect);

        const length = 2 * (r.width + r.height);
        rect.style.strokeDasharray = `${length}`;
        rect.style.strokeDashoffset = `${length}`;

        return { rect, length };
    });
}

// Boot completion → draw real page and reveal
watch(isBoot, (boot) => {
    if (!boot) {
        drawAndReveal();
    }
});

// ------------------------------------------------------------- reveal
function drawAndReveal() {
    const overlayEl = overlay.value;

    if (!overlayEl) {
        return;
    }

    const regions = measurePage();
    const drawn = drawRegions(regions);

    animations.forEach((a) => a.cancel());
    animations.clear();

    if (prefersReducedMotion.value) {
        // Skip animation, just set end state
        drawn.forEach(({ rect }) => {
            rect.style.strokeDashoffset = '0';
        });
        overlayEl.style.opacity = '0';
        overlayEl.style.visibility = 'hidden';

        return;
    }

    // Same timeline the gsap version played: rects draw in with a 0.08s
    // stagger (0.5s, power2.out), then the overlay fades 0.15s after the
    // last stroke completes (0.4s, power2.inOut).
    drawn.forEach(({ rect, length }, i) => {
        const anim = rect.animate(
            [{ strokeDashoffset: `${length}` }, { strokeDashoffset: '0' }],
            {
                duration: 500,
                delay: i * 80,
                easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
                fill: 'both',
            },
        );
        animations.add(anim);
    });

    const lastStagger = Math.max(0, (drawn.length - 1) * 80);
    const fade = overlayEl.animate([{ opacity: 1 }, { opacity: 0 }], {
        duration: 400,
        delay: lastStagger + 500 + 150,
        easing: 'cubic-bezier(0.455, 0.03, 0.515, 0.955)',
        fill: 'forwards',
    });
    animations.add(fade);
    fade.onfinish = () => {
        overlayEl.style.visibility = 'hidden';
    };
}

onMounted(() => {
    // If boot already completed before mount (e.g. very fast page), reveal immediately.
    if (!isBoot.value) {
        drawAndReveal();
    }
});

onBeforeUnmount(() => {
    animations.forEach((a) => a.cancel());
    animations.clear();

    if (reducedMotionMql && reducedMotionHandler) {
        reducedMotionMql.removeEventListener('change', reducedMotionHandler);
    }
});
</script>

<template>
    <!-- Top drafting line -->
    <div
        class="page-loader-bar pointer-events-none fixed top-0 right-0 left-0 z-[9999] h-[3px]"
        :class="{ 'is-spa-active': isSpaActive }"
    >
        <div
            class="page-loader-bar-inner h-full origin-left"
            :style="{ transform: `scaleX(${progress})` }"
        />
    </div>

    <!-- Full overlay with self-drawing SVG (boot canvas) -->
    <div
        ref="overlay"
        class="d-dots-bg pointer-events-none fixed inset-0 z-[9998]"
    >
        <svg
            ref="svgHost"
            class="absolute inset-0 h-full w-full"
            xmlns="http://www.w3.org/2000/svg"
        />
        <p
            class="d-label pointer-events-none absolute bottom-6 left-1/2 -translate-x-1/2 text-(--accent)"
        >
            Drafting…
        </p>
    </div>
</template>

<style scoped>
.page-loader-bar {
    background: var(--accent-soft);
    opacity: 0;
    visibility: hidden;
    transition:
        opacity 0.3s cubic-bezier(0.455, 0.03, 0.515, 0.955),
        visibility 0s linear 0.3s;
}

.page-loader-bar.is-spa-active {
    opacity: 1;
    visibility: visible;
    transition:
        opacity 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94),
        visibility 0s;
}

.page-loader-bar-inner {
    background: var(--accent);
    transform: scaleX(0);
    transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

@media (prefers-reduced-motion: reduce) {
    .page-loader-bar,
    .page-loader-bar-inner {
        transition: none;
    }
}
</style>
