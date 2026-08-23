<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowDownRight, ArrowUpRight } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { clickSeries, kpis, topPages, visitorSeries } from '@/data/analytics';
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

const visitors = visitorSeries(14);
const clicks = clickSeries(14);

const kpiText = (kpi: (typeof kpis)[number]) => {
    if (kpi.format === 'percent') {
        return `${kpi.value.toFixed(1)}%`;
    }

    return kpi.value.toLocaleString('en-US');
};

// Bar chart geometry
const W = 640;
const H = 200;
const PAD = { top: 12, right: 8, bottom: 28, left: 8 };
const chartW = W - PAD.left - PAD.right;
const chartH = H - PAD.top - PAD.bottom;
const maxVal = computed(() =>
    Math.max(...visitors.map((d) => d.value), ...clicks.map((d) => d.value)),
);
const step = chartW / visitors.length;

function yFor(v: number) {
    return PAD.top + chartH - (v / maxVal.value) * chartH;
}

const visitorPath = computed(() =>
    visitors
        .map((d, i) => {
            const x = PAD.left + step * i + step / 2;
            const y = yFor(d.value);

            return `${i === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' '),
);

const clickPath = computed(() =>
    clicks
        .map((d, i) => {
            const x = PAD.left + step * i + step / 2;
            const y = yFor(d.value);

            return `${i === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' '),
);

const gridLines = [0.25, 0.5, 0.75, 1];

const totalCtr = computed(() => {
    const v = visitors.reduce((a, d) => a + d.value, 0);
    const c = clicks.reduce((a, d) => a + d.value, 0);

    return v ? (c / v) * 100 : 0;
});
</script>

<template>
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Dashboard" />

        <Heading
            title="Overview"
            description="A snapshot of how the public site is performing over the last two weeks."
        />

        <!-- KPI cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card v-for="kpi in kpis" :key="kpi.key" class="overflow-hidden">
                <CardHeader class="pb-2">
                    <CardTitle
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        {{ kpi.label }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-end justify-between gap-2">
                        <p
                            class="font-mono text-3xl font-semibold tracking-tight tabular-nums"
                        >
                            {{ kpiText(kpi) }}
                        </p>
                        <span
                            class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 font-mono text-xs tabular-nums"
                            :class="
                                kpi.delta >= 0
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300'
                            "
                        >
                            <ArrowUpRight
                                v-if="kpi.delta >= 0"
                                class="size-3"
                            />
                            <ArrowDownRight v-else class="size-3" />
                            {{ Math.abs(kpi.delta).toFixed(1) }}%
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Chart -->
        <Card>
            <CardHeader>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <CardTitle class="text-sm font-medium"
                        >Traffic · last 14 days</CardTitle
                    >
                    <div
                        class="flex items-center gap-4 font-mono text-xs text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <span
                                class="size-2 rounded-full bg-accent-primary"
                            />
                            Visitors
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span
                                class="size-2 rounded-full bg-accent-secondary"
                            />
                            Clicks
                        </span>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <svg
                    viewBox="0 0 640 200"
                    class="h-48 w-full"
                    role="img"
                    aria-label="Line chart of visitors and clicks over the last 14 days"
                    preserveAspectRatio="none"
                >
                    <!-- grid lines -->
                    <line
                        v-for="g in gridLines"
                        :key="g"
                        :x1="PAD.left"
                        :x2="W - PAD.right"
                        :y1="PAD.top + chartH * (1 - g)"
                        :y2="PAD.top + chartH * (1 - g)"
                        class="stroke-border"
                        stroke-width="1"
                    />
                    <path
                        :d="visitorPath"
                        fill="none"
                        class="stroke-accent-primary"
                        stroke-width="2"
                    />
                    <path
                        :d="clickPath"
                        fill="none"
                        class="stroke-accent-secondary"
                        stroke-width="2"
                    />
                </svg>
                <div
                    class="mt-2 flex justify-between font-mono text-[10px] tracking-wide text-muted-foreground uppercase"
                >
                    <span>{{ visitors[0]?.label }}</span>
                    <span>{{ visitors[visitors.length - 1]?.label }}</span>
                </div>
            </CardContent>
        </Card>

        <!-- Top pages + CTR summary -->
        <div class="grid gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle class="text-sm font-medium">Top pages</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-border text-left font-mono text-xs tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-4 py-2 font-medium">Page</th>
                                <th class="px-4 py-2 text-right font-medium">
                                    Visitors
                                </th>
                                <th class="px-4 py-2 text-right font-medium">
                                    Clicks
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="page in topPages"
                                :key="page.path"
                                class="border-b border-border/60 last:border-0"
                            >
                                <td class="px-4 py-2.5">
                                    <p class="font-medium">{{ page.title }}</p>
                                    <p
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ page.path }}
                                    </p>
                                </td>
                                <td
                                    class="px-4 py-2.5 text-right font-mono tabular-nums"
                                >
                                    {{ page.visitors.toLocaleString('en-US') }}
                                </td>
                                <td
                                    class="px-4 py-2.5 text-right font-mono tabular-nums"
                                >
                                    {{ page.clicks.toLocaleString('en-US') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium">Funnel</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-5">
                        <div>
                            <div
                                class="mb-1 flex items-baseline justify-between"
                            >
                                <span class="text-xs text-muted-foreground"
                                    >Clicks / visitors</span
                                >
                                <span class="font-mono text-sm tabular-nums">
                                    {{ totalCtr.toFixed(1) }}%
                                </span>
                            </div>
                            <div
                                class="h-1.5 overflow-hidden rounded-full bg-secondary"
                            >
                                <div
                                    class="h-full rounded-full bg-accent-primary"
                                    :style="{
                                        width: `${Math.min(totalCtr, 100)}%`,
                                    }"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-md border bg-muted/40 p-3">
                                <p class="font-mono text-lg tabular-nums">
                                    {{
                                        visitors
                                            .reduce((a, d) => a + d.value, 0)
                                            .toLocaleString('en-US')
                                    }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Visitors
                                </p>
                            </div>
                            <div class="rounded-md border bg-muted/40 p-3">
                                <p class="font-mono text-lg tabular-nums">
                                    {{
                                        clicks
                                            .reduce((a, d) => a + d.value, 0)
                                            .toLocaleString('en-US')
                                    }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Clicks
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
