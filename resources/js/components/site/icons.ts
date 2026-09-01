import { h } from 'vue';
import type { FunctionalComponent } from 'vue';

/**
 * Inline SVG equivalents of the lucide icons used by the eager boot chain
 * (site header/footer, homepage hero). Keeping these out of `@lucide/vue`
 * moves the shared lucide runtime chunk off the public critical path —
 * it is then fetched only with lazy chunks (home sections, posts page,
 * dashboard). Path data and rendering attributes mirror lucide exactly
 * (24px viewBox, round stroke caps/joins), so the visuals are identical.
 */
type IconNode = [
    tag: 'path' | 'circle' | 'rect',
    attrs: Record<string, string>,
];

const makeIcon =
    (nodes: IconNode[]): FunctionalComponent =>
    (_, { attrs }) =>
        h(
            'svg',
            {
                xmlns: 'http://www.w3.org/2000/svg',
                width: 24,
                height: 24,
                viewBox: '0 0 24 24',
                fill: 'none',
                stroke: 'currentColor',
                'stroke-width': 2,
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                ...attrs,
            },
            nodes.map(([tag, nodeAttrs]) => h(tag, nodeAttrs)),
        );

export const ArrowLeft = makeIcon([
    ['path', { d: 'm12 19-7-7 7-7' }],
    ['path', { d: 'M19 12H5' }],
]);

export const ArrowUpRight = makeIcon([
    ['path', { d: 'M7 7h10v10' }],
    ['path', { d: 'M7 17 17 7' }],
]);

export const Briefcase = makeIcon([
    ['path', { d: 'M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16' }],
    ['rect', { width: '20', height: '14', x: '2', y: '6', rx: '2' }],
]);

export const Mail = makeIcon([
    ['rect', { width: '20', height: '16', x: '2', y: '4', rx: '2' }],
    ['path', { d: 'm22 7-8.97 5.72a2 2 0 0 1-2.06 0L2 7' }],
]);

export const MapPin = makeIcon([
    [
        'path',
        {
            d: 'M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0',
        },
    ],
    ['circle', { cx: '12', cy: '10', r: '3' }],
]);

export const Sparkles = makeIcon([
    [
        'path',
        {
            d: 'M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z',
        },
    ],
    ['path', { d: 'M20 2v4' }],
    ['path', { d: 'M22 4h-4' }],
    ['circle', { cx: '4', cy: '20', r: '2' }],
]);

export const Menu = makeIcon([
    ['path', { d: 'M4 5h16' }],
    ['path', { d: 'M4 12h16' }],
    ['path', { d: 'M4 19h16' }],
]);

export const Moon = makeIcon([
    [
        'path',
        {
            d: 'M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401',
        },
    ],
]);

export const Sun = makeIcon([
    ['circle', { cx: '12', cy: '12', r: '4' }],
    ['path', { d: 'M12 2v2' }],
    ['path', { d: 'M12 20v2' }],
    ['path', { d: 'm4.93 4.93 1.41 1.41' }],
    ['path', { d: 'm17.66 17.66 1.41 1.41' }],
    ['path', { d: 'M2 12h2' }],
    ['path', { d: 'M20 12h2' }],
    ['path', { d: 'm6.34 17.66-1.41 1.41' }],
    ['path', { d: 'm19.07 4.93-1.41 1.41' }],
]);

export const X = makeIcon([
    ['path', { d: 'M18 6 6 18' }],
    ['path', { d: 'm6 6 12 12' }],
]);
