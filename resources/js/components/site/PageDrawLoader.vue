<script setup lang="ts">
/**
 * PageDrawLoader — the destination page draws itself into existence.
 *
 * Boot: a paper-colored canvas covers the screen; when Inertia finishes
 * mounting (`page-loader:boot-complete`), the real page layout is measured
 * and drawn as SVG strokes that resolve into the live page. The full
 * overlay is boot-only.
 *
 * SPA: `inertia:start` / `inertia:finish` drive a lightweight top
 * "drafting line" bar tied to real progress.
 *
 * Degrades to a static reveal when prefers-reduced-motion is set, and a
 * 3s hard cap in the composable guarantees the page is never blocked.
 */
import { gsap } from 'gsap';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePageLoader } from '@/composables/usePageLoader';

const { progress, isSpaActive, isBoot } = usePageLoader();

const overlay = ref<HTMLDivElement | null>(null);
const svgHost = ref<SVGSVGElement | null>(null);
const topBar = ref<HTMLDivElement | null>(null);
const topBarInner = ref<HTMLDivElement | null>(null);

let progressTo: gsap.QuickToFunc | null = null;
let revealTimeline: gsap.core.Timeline | null = null;

// Reduced-motion detection (synchronous initialization)
const prefersReducedMotion = ref(false);

if (typeof window !== 'undefined') {
    const mql = window.matchMedia('(prefers-reduced-motion: reduce)');
    prefersReducedMotion.value = mql.matches;
    mql.addEventListener('change', (e) => {
        prefersReducedMotion.value = e.matches;
    });
}

// ------------------------------------------------------------- top bar
// Setup progress bar quickTo for smooth updates
watch(topBarInner, (el) => {
    if (el) {
        progressTo = gsap.quickTo(el, 'scaleX', { duration: 0.3, ease: 'power2.out' });
    }
});

// Watch progress → update top bar
watch(progress, (p) => {
    if (progressTo) {
        progressTo(p);
    } else if (topBarInner.value) {
        gsap.to(topBarInner.value, { scaleX: p, duration: 0.3, ease: 'power2.out' });
    }
});

// SPA active → top bar visibility
watch(isSpaActive, (active) => {
    if (active) {
        if (topBar.value) {
            gsap.fromTo(topBar.value, { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.2, ease: 'power2.out' });
        }
    } else {
        if (topBar.value) {
            gsap.to(topBar.value, { autoAlpha: 0, duration: 0.3, ease: 'power2.inOut' });
        }
    }
});

// --------------------------------------------------------- drawing
function measurePage(): Array<{ x: number; y: number; width: number; height: number }> {
    const regions: Array<{ x: number; y: number; width: number; height: number }> = [];

    // Measure semantic landmarks
    const landmarks = ['header', 'main', 'footer', 'nav'];
    landmarks.forEach((tag) => {
        const els = document.querySelectorAll(tag);
        els.forEach((el) => {
            const rect = el.getBoundingClientRect();

            if (rect.width > 0 && rect.height > 0) {
                regions.push({ x: rect.left, y: rect.top, width: rect.width, height: rect.height });
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
                    regions.push({ x: rect.left, y: rect.top, width: rect.width, height: rect.height });
                }
            }
        }
    }

    return regions;
}

function drawRegions(regions: Array<{ x: number; y: number; width: number; height: number }>) {
    if (!svgHost.value) {
return [];
}

    const svg = svgHost.value;

    // Clear previous rects
    while (svg.firstChild) {
        svg.removeChild(svg.firstChild);
    }

    return regions.map((r) => {
        const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
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
        gsap.set(rect, { strokeDasharray: length, strokeDashoffset: length });

        return rect;
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
    if (!overlay.value) {
return;
}

    const regions = measurePage();
    const rects = drawRegions(regions);

    revealTimeline?.kill();

    if (prefersReducedMotion.value) {
        // Skip animation, just set end state
        rects.forEach((rect) => {
            gsap.set(rect, { strokeDashoffset: 0 });
        });
        gsap.set(overlay.value, { autoAlpha: 0 });

        return;
    }

    revealTimeline = gsap.timeline();
    revealTimeline
        .to(rects, {
            strokeDashoffset: 0,
            duration: 0.5,
            stagger: 0.08,
            ease: 'power2.out',
        })
        .to(overlay.value, {
            autoAlpha: 0,
            duration: 0.4,
            ease: 'power2.inOut',
        }, '+=0.15');
}

onMounted(() => {
    // If boot already completed before mount (e.g. very fast page), reveal immediately.
    if (!isBoot.value) {
        drawAndReveal();
    }
});

onBeforeUnmount(() => {
    revealTimeline?.kill();
});
</script>

<template>
    <!-- Top drafting line -->
    <div
        ref="topBar"
        class="fixed left-0 right-0 top-0 z-[9999] h-[3px] pointer-events-none"
        style="background: var(--accent-soft); opacity: 0; visibility: hidden;"
    >
        <div
            ref="topBarInner"
            class="h-full origin-left"
            style="background: var(--accent); transform: scaleX(0);"
        />
    </div>

    <!-- Full overlay with self-drawing SVG (boot canvas) -->
    <div
        ref="overlay"
        class="fixed inset-0 z-[9998] pointer-events-none"
        style="background: var(--paper);"
    >
        <svg
            ref="svgHost"
            class="absolute inset-0 w-full h-full"
            xmlns="http://www.w3.org/2000/svg"
        />
    </div>
</template>
