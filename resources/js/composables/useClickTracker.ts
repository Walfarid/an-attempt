import { store as storeClickRoute } from '@/routes/analytics/clicks';

/**
 * Lightweight click tracker — sends click events to the server for real analytics.
 * Only tracks public-facing pages (not dashboard).
 */
export function useClickTracker() {
    /**
     * Track a click on a public page element.
     */
    function trackClick(options: { element?: string; label?: string }) {
        const path = window.location.pathname;

        // Skip tracking on dashboard/admin pages
        if (path.startsWith('/dashboard')) {
            return;
        }

        const payload = {
            path,
            element: options.element ?? null,
            label: options.label ?? null,
        };

        // Use a lightweight XHR that doesn't interfere with Inertia navigation
        fetch(storeClickRoute.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(payload),
            keepalive: true,
        }).catch(() => {
            // Silently fail — analytics should never break the app
        });
    }

    return { trackClick };
}

function getCsrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta?.getAttribute('content') ?? '';
}

/**
 * Auto-track clicks on elements with data-track attribute.
 * Usage: <a href="/projects" data-track="project-link" data-track-label="Project 1">
 */
// HMR guard: prevent duplicate listener registration across hot reloads
let autoTrackerInitialized = false;

export function initAutoClickTracker() {
    if (typeof document === 'undefined') {
        return;
    }

    // HMR guard: skip if already initialized
    if (autoTrackerInitialized) {
        return;
    }

    autoTrackerInitialized = true;

    document.addEventListener('click', (e) => {
        const target = e.target as HTMLElement;
        const tracked = target.closest('[data-track]');

        if (!tracked) {
            return;
        }

        const element = tracked.getAttribute('data-track') ?? undefined;
        const label = tracked.getAttribute('data-track-label') ?? undefined;

        const { trackClick } = useClickTracker();
        trackClick({ element, label });
    });
}
