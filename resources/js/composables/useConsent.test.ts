import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useConsent } from './useConsent';

function clearConsentCookie() {
    document.cookie = 'consent=;path=/;max-age=0';
}

describe('useConsent', () => {
    beforeEach(() => {
        clearConsentCookie();
        vi.stubGlobal('reload', vi.fn());
        vi.stubGlobal('gtag', vi.fn());
        // Stub window.location.reload
        const originalLocation = window.location;
        Object.defineProperty(window, 'location', {
            configurable: true,
            value: { ...originalLocation, reload: vi.fn() },
        });
    });

    afterEach(() => {
        clearConsentCookie();
        vi.unstubAllGlobals();
    });

    it('shows the banner when no consent cookie exists', () => {
        const { checkConsent, bannerVisible, consent } = useConsent();

        checkConsent();

        expect(bannerVisible.value).toBe(true);
        expect(consent.value).toBeNull();
    });

    it('parses an existing accepted consent cookie', () => {
        document.cookie = 'consent=accepted;path=/';
        const { checkConsent, bannerVisible, consent } = useConsent();

        checkConsent();

        expect(consent.value).toBe('accepted');
        expect(bannerVisible.value).toBe(false);
    });

    it('parses an existing declined consent cookie', () => {
        document.cookie = 'consent=declined;path=/';
        const { checkConsent, bannerVisible, consent } = useConsent();

        checkConsent();

        expect(consent.value).toBe('declined');
        expect(bannerVisible.value).toBe(false);
    });

    it('ignores invalid cookie values', () => {
        document.cookie = 'consent=maybe;path=/';
        const { checkConsent, consent } = useConsent();

        checkConsent();

        expect(consent.value).toBeNull();
    });

    it('stores consent as a cookie via acceptAll and hides the banner', () => {
        const { acceptAll, consent, bannerVisible } = useConsent();

        acceptAll();

        expect(consent.value).toBe('accepted');
        expect(bannerVisible.value).toBe(false);
        expect(document.cookie).toMatch(/consent=accepted/);
        expect(window.location.reload).toHaveBeenCalled();
    });

    it('stores consent as a cookie via declineAll and hides the banner', () => {
        const { declineAll, consent, bannerVisible } = useConsent();

        declineAll();

        expect(consent.value).toBe('declined');
        expect(bannerVisible.value).toBe(false);
        expect(document.cookie).toMatch(/consent=declined/);
        expect(window.location.reload).toHaveBeenCalled();
    });

    it('clears the cookie and re-shows the banner on openCookieSettings', () => {
        const { acceptAll, openCookieSettings, consent, bannerVisible } =
            useConsent();

        acceptAll();
        openCookieSettings();

        expect(consent.value).toBeNull();
        expect(bannerVisible.value).toBe(true);
    });

    it('pushes consent mode v2 update via gtag on accept', () => {
        const gtagMock = vi.fn();
        vi.stubGlobal('gtag', gtagMock);

        const { acceptAll } = useConsent();
        acceptAll();

        expect(gtagMock).toHaveBeenCalledWith('consent', 'update', {
            ad_storage: 'granted',
            ad_user_data: 'granted',
            ad_personalization: 'granted',
            analytics_storage: 'granted',
        });
    });

    it('pushes consent mode v2 update via gtag on decline', () => {
        const gtagMock = vi.fn();
        vi.stubGlobal('gtag', gtagMock);

        const { declineAll } = useConsent();
        declineAll();

        expect(gtagMock).toHaveBeenCalledWith('consent', 'update', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
        });
    });
});
