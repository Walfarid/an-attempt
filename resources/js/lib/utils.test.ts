import { describe, expect, it } from 'vitest';
import { cn, formatDate, toUrl } from './utils';

describe('cn', () => {
    it('merges class names', () => {
        expect(cn('px-2', 'py-1')).toBe('px-2 py-1');
    });

    it('resolves conflicting Tailwind utilities', () => {
        expect(cn('px-2', 'px-4')).toBe('px-4');
    });

    it('handles conditional classes', () => {
        expect(cn('base', false && 'hidden', 'extra')).toBe('base extra');
    });

    it('returns empty string for no inputs', () => {
        expect(cn()).toBe('');
    });
});

describe('toUrl', () => {
    it('returns string hrefs as-is', () => {
        expect(toUrl('/about')).toBe('/about');
    });

    it('extracts url from an Inertia link object', () => {
        expect(toUrl({ url: '/posts', method: 'get' } as never)).toBe('/posts');
    });
});

describe('formatDate', () => {
    it('formats an ISO date string in en-US short format', () => {
        expect(formatDate('2025-06-15T00:00:00Z')).toMatch(/Jun 15, 2025/);
    });

    it('handles single-digit days without leading zero', () => {
        const result = formatDate('2025-01-03T00:00:00Z');
        expect(result).toMatch(/Jan 3, 2025/);
    });
});
