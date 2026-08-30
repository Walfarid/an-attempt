<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    TrendingDown,
    TrendingUp,
    Users,
    MousePointerClick,
    BarChart3,
    Eye,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted } from 'vue';
import Heading from '@/components/Heading.vue';
import { useScrollAnimations } from '@/composables/useScrollAnimations';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

type Kpi = {
    key: string;
    label: string;
    value: number;
    delta: number;
    format?: 'number' | 'percent' | 'duration';
};

type SeriesPoint = {
    label: string;
    value: number;
};

type TopPage = {
    path: string;
    title: string;
    visitors: number;
    clicks: number;
};

const props = defineProps<{
    kpis: Kpi[];
    visitorSeries: SeriesPoint[];
    clickSeries: SeriesPoint[];
    topPages: TopPage[];
}>();

const visitors = computed(() => props.visitorSeries);
const clicks = computed(() => props.clickSeries);

const kpiText = (kpi: Kpi) => {
    if (kpi.format === 'percent') {
        return `${kpi.value.toFixed(1)}%`;
    }

    return kpi.value.toLocaleString('en-US');
};

const kpiIcons: Record<string, typeof Users> = {
    visitors: Users,
    clicks: MousePointerClick,
    ctr: BarChart3,
    pageviews: Eye,
};

// Bar chart geometry
const W = 640;
const H = 200;
const PAD = { top: 12, right: 8, bottom: 28, left: 8 };
const chartW = W - PAD.left - PAD.right;
const chartH = H - PAD.top - PAD.bottom;
const maxVal = computed(() =>
    Math.max(
        ...visitors.value.map((d) => d.value),
        ...clicks.value.map((d) => d.value),
    ),
);
const step = computed(() => chartW / visitors.value.length);

function yFor(v: number) {
    return PAD.top + chartH - (v / maxVal.value) * chartH;
}

const visitorPath = computed(() =>
    visitors.value
        .map((d, i) => {
            const x = PAD.left + step.value * i + step.value / 2;
            const y = yFor(d.value);

            return `${i === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' '),
);

const clickPath = computed(() =>
    clicks.value
        .map((d, i) => {
            const x = PAD.left + step.value * i + step.value / 2;
            const y = yFor(d.value);

            return `${i === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' '),
);

const gridLines = [0.25, 0.5, 0.75, 1];

const totalVisitors = computed(() =>
    visitors.value.reduce((a, d) => a + d.value, 0),
);
const totalClicks = computed(() =>
    clicks.value.reduce((a, d) => a + d.value, 0),
);

const totalCtr = computed(() => {
    const v = totalVisitors.value;
    const c = totalClicks.value;

    return v ? (c / v) * 100 : 0;
});

useScrollAnimations();

/* One-time chart draw-in -------------------------------------------------- */

let chartMm: gsap.MatchMedia | null = null;

onMounted(async () => {
    const [{ gsap }, { ScrollTrigger }] = await Promise.all([
        import('gsap'),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);

    chartMm = gsap.matchMedia();

    chartMm.add('(prefers-reduced-motion: no-preference)', () => {
        const lines = Array.from(
            document.querySelectorAll<SVGPathElement>('.chart-line'),
        );

        if (!lines.length) {
            return;
        }

        lines.forEach((el) => {
            const length = el.getTotalLength();

            gsap.set(el, { strokeDasharray: length, strokeDashoffset: length });
        });

        gsap.to(lines, {
            strokeDashoffset: 0,
            duration: 1,
            stagger: 0.15,
            ease: 'power2.inOut',
            scrollTrigger: {
                trigger: lines[0],
                start: 'top 88%',
                once: true,
            },
        });
    });
});

onUnmounted(() => {
    chartMm?.revert();
    chartMm = null;
});
</script>

<template>
    <div class="d-dots-bg flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Dashboard" />

        <Heading
            title="Overview"
            description="A snapshot of how the public site is performing over the last two weeks."
            section-number="01"
        />

        <!-- KPI Grid -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" data-motion-group>
            <div
                v-for="kpi in kpis"
                :key="kpi.key"
                class="d-surface p-4 transition-shadow hover:shadow-sm"
                data-motion
            >
                <div class="flex items-center justify-between">
                    <div class="d-label mb-2">{{ kpi.label }}</div>
                    <component
                        :is="kpiIcons[kpi.key] ?? Users"
                        class="size-4 text-(--ink-soft)"
                        aria-hidden="true"
                    />
                </div>
                <div class="flex items-end justify-between gap-2">
                    <p
                        class="font-display text-2xl font-bold tracking-tight tabular-nums sm:text-3xl"
                    >
                        {{ kpiText(kpi) }}
                    </p>
                    <span
                        class="inline-flex items-center gap-0.5 px-2 py-0.5 text-xs font-medium tabular-nums"
                        :class="
                            kpi.delta >= 0
                                ? 'bg-(--accent-soft) text-(--accent)'
                                : 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-400'
                        "
                    >
                        <TrendingUp
                            v-if="kpi.delta >= 0"
                            class="size-3"
                            aria-hidden="true"
                        />
                        <TrendingDown
                            v-else
                            class="size-3"
                            aria-hidden="true"
                        />
                        {{ Math.abs(kpi.delta).toFixed(1) }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Traffic Chart -->
        <div class="d-surface" data-motion>
            <div class="border-b border-(--rule) px-4 py-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-display text-sm font-semibold">
                        Traffic · last 14 days
                    </h3>
                    <div
                        class="flex items-center gap-4 text-xs text-(--ink-soft)"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <span
                                class="inline-block h-2 w-2 rounded-full bg-(--accent)"
                            />
                            Visitors
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span
                                class="inline-block h-2 w-2 rounded-full bg-(--accent-secondary)"
                            />
                            Clicks
                        </span>
                    </div>
                </div>
            </div>
            <div class="p-4">
                <svg
                    viewBox="0 0 640 200"
                    class="h-48 w-full"
                    role="img"
                    aria-label="Line chart of visitors and clicks over the last 14 days"
                    preserveAspectRatio="none"
                >
                    <!-- Grid lines -->
                    <line
                        v-for="g in gridLines"
                        :key="g"
                        :x1="PAD.left"
                        :x2="W - PAD.right"
                        :y1="PAD.top + chartH * (1 - g)"
                        :y2="PAD.top + chartH * (1 - g)"
                        stroke="var(--rule)"
                        stroke-width="0.5"
                    />
                    <path
                        :d="visitorPath"
                        class="chart-line"
                        fill="none"
                        stroke="var(--accent)"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        :d="clickPath"
                        class="chart-line"
                        fill="none"
                        stroke="var(--accent-secondary)"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
                <div
                    class="mt-2 flex justify-between text-xs text-(--ink-soft)"
                >
                    <span>{{ visitors[0]?.label }}</span>
                    <span>{{ visitors[visitors.length - 1]?.label }}</span>
                </div>
            </div>
        </div>

        <!-- Top Pages + Funnel -->
        <div class="grid gap-3 lg:grid-cols-3">
            <!-- Top Pages Table -->
            <div class="d-surface lg:col-span-2" data-motion>
                <div class="border-b border-(--rule) px-4 py-3">
                    <h3 class="font-display text-sm font-semibold">
                        Top pages
                    </h3>
                </div>
                <table class="d-table">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th class="text-right">Visitors</th>
                            <th class="text-right">Clicks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="page in topPages" :key="page.path">
                            <td>
                                <p class="font-medium">{{ page.title }}</p>
                                <p class="text-xs text-(--ink-soft)">
                                    {{ page.path }}
                                </p>
                            </td>
                            <td class="text-right tabular-nums">
                                {{ page.visitors.toLocaleString('en-US') }}
                            </td>
                            <td class="text-right tabular-nums">
                                {{ page.clicks.toLocaleString('en-US') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Funnel -->
            <div class="d-surface" data-motion>
                <div class="border-b border-(--rule) px-4 py-3">
                    <h3 class="font-display text-sm font-semibold">Funnel</h3>
                </div>
                <div class="space-y-4 p-4">
                    <div>
                        <div class="mb-1.5 flex items-baseline justify-between">
                            <span class="text-xs text-(--ink-soft)"
                                >Clicks / visitors</span
                            >
                            <span
                                class="font-display text-sm font-semibold tabular-nums"
                            >
                                {{ totalCtr.toFixed(1) }}%
                            </span>
                        </div>
                        <div class="h-2 bg-(--accent-soft)">
                            <div
                                class="h-full bg-(--accent) transition-all"
                                :style="{
                                    width: `${Math.min(totalCtr, 100)}%`,
                                }"
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-(--accent-soft) p-3">
                            <p
                                class="font-display text-lg font-semibold tabular-nums"
                            >
                                {{ totalVisitors.toLocaleString('en-US') }}
                            </p>
                            <p class="text-xs text-(--ink-soft)">Visitors</p>
                        </div>
                        <div class="bg-(--accent-soft) p-3">
                            <p
                                class="font-display text-lg font-semibold tabular-nums"
                            >
                                {{ totalClicks.toLocaleString('en-US') }}
                            </p>
                            <p class="text-xs text-(--ink-soft)">Clicks</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
