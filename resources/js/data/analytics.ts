/**
 * Faked portfolio analytics — powers the dashboard overview. Replace with a
 * real analytics backend later.
 */

export type Kpi = {
    key: string;
    label: string;
    value: number;
    delta: number; // % vs previous period
    format?: 'number' | 'percent' | 'duration';
};

export type SeriesPoint = {
    label: string;
    value: number;
};

export type TopPage = {
    path: string;
    title: string;
    visitors: number;
    clicks: number;
};

const seed = 42;
const rand = (n: number) => {
    // Deterministic pseudo-random so the dashboard is stable between reloads.
    const x = Math.sin(n * 999 + seed) * 10000;

    return x - Math.floor(x);
};

/** Last 14 days, most recent last. */
export function visitorSeries(days = 14): SeriesPoint[] {
    const out: SeriesPoint[] = [];
    const now = new Date();

    for (let i = days - 1; i >= 0; i--) {
        const d = new Date(now);
        d.setDate(d.getDate() - i);
        out.push({
            label: d.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
            }),
            value: Math.round(80 + rand(i + 1) * 200),
        });
    }

    return out;
}

export function clickSeries(days = 14): SeriesPoint[] {
    const out: SeriesPoint[] = [];
    const now = new Date();

    for (let i = days - 1; i >= 0; i--) {
        const d = new Date(now);
        d.setDate(d.getDate() - i);
        out.push({
            label: d.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
            }),
            value: Math.round(12 + rand(i + 50) * 90),
        });
    }

    return out;
}

export const kpis: Kpi[] = [
    {
        key: 'visitors',
        label: 'Visitors',
        value: 12847,
        delta: 12.4,
        format: 'number',
    },
    {
        key: 'clicks',
        label: 'Clicks',
        value: 3912,
        delta: 8.1,
        format: 'number',
    },
    {
        key: 'ctr',
        label: 'Click-through rate',
        value: 30.5,
        delta: -2.3,
        format: 'percent',
    },
    {
        key: 'pageviews',
        label: 'Pageviews',
        value: 18762,
        delta: 15.9,
        format: 'number',
    },
];

export const topPages: TopPage[] = [
    { path: '/', title: 'Home', visitors: 4832, clicks: 2015 },
    {
        path: '/#projects',
        title: 'Projects anchor',
        visitors: 2901,
        clicks: 1240,
    },
    {
        path: '/#experience',
        title: 'Experience anchor',
        visitors: 1735,
        clicks: 401,
    },
    { path: '/dashboard', title: 'Dashboard', visitors: 411, clicks: 256 },
];
