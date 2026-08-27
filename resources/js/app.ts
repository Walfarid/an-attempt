import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, defineComponent, h } from 'vue';
import CookieConsentBanner from '@/components/CookieConsentBanner.vue';
import PageDrawLoader from '@/components/site/PageDrawLoader.vue';
import { initializeTheme } from '@/composables/useAppearance';
import { initAutoClickTracker } from '@/composables/useClickTracker';
import { initRouteTransition } from '@/composables/useRouteTransition';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
            case name === 'Privacy':
            case name.startsWith('posts/'):
                return null;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    // Enable progress events for the self-drawing page loader.
    progress: { color: '#17594a', showSpinner: false },
    setup({ el, App, props, plugin }) {
        if (!el) {
            return;
        }

        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.mount(el);

        // Subtle rise-in for SPA navigations (skips the landing page).
        initRouteTransition(el);

        // The page is now rendered; tell the loader to draw it and reveal.
        window.dispatchEvent(new CustomEvent('page-loader:boot-complete'));

        return app;
    },
});

// Mount the self-drawing page loader. It lives outside the Inertia root
// so it survives SPA navigation and listens to Inertia progress events.
const loaderEl = document.createElement('div');
loaderEl.id = 'page-loader-root';
document.body.appendChild(loaderEl);
createApp(defineComponent({ render: () => h(PageDrawLoader) })).mount(loaderEl);

// The consent banner also lives outside the Inertia root so it shows on
// every page (public and dashboard) and never gets hidden by the SPA
// route-transition fade that targets the root.
const consentEl = document.createElement('div');
consentEl.id = 'consent-banner-root';
document.body.appendChild(consentEl);
createApp(defineComponent({ render: () => h(CookieConsentBanner) })).mount(
    consentEl,
);

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

// Auto-track clicks on elements with data-track attribute...
initAutoClickTracker();
