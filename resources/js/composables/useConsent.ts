import { ref } from 'vue';

export type ConsentChoice = 'accepted' | 'declined';

/**
 * Module-level so the banner (mounted outside the Inertia root), the
 * privacy page's cookie settings and page components share one
 * visibility/consent state.
 */
const bannerVisible = ref(false);

/** The stored choice as reactive state ('accepted' | 'declined' | null). */
const consent = ref<ConsentChoice | null>(null);

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const clearCookie = (name: string) => {
    if (typeof document === 'undefined') {
        return;
    }

    document.cookie = `${name}=;path=/;max-age=0;SameSite=Lax`;
};

const getStoredConsent = (): ConsentChoice | null => {
    if (typeof document === 'undefined') {
        return null;
    }

    const match = document.cookie.match(/(?:^|;\s*)consent=([^;]*)/);

    return match?.[1] === 'accepted' || match?.[1] === 'declined'
        ? (match[1] as ConsentChoice)
        : null;
};

/** Push a Google Consent Mode v2 update via gtag. */
const pushConsentUpdate = (state: 'granted' | 'denied') => {
    if (typeof window !== 'undefined' && typeof window.gtag === 'function') {
        window.gtag('consent', 'update', {
            ad_storage: state,
            ad_user_data: state,
            ad_personalization: state,
            analytics_storage: state,
        });
    }
};

export type UseConsentReturn = {
    bannerVisible: ReturnType<typeof ref<boolean>>;
    consent: ReturnType<typeof ref<ConsentChoice | null>>;
    checkConsent: () => void;
    acceptAll: () => void;
    declineAll: () => void;
    openCookieSettings: () => void;
};

export function useConsent(): UseConsentReturn {
    /** Show the banner only while no stored choice exists. */
    function checkConsent(): void {
        consent.value = getStoredConsent();
        bannerVisible.value = consent.value === null;
    }

    /** Persist the choice; the banner hides for good until it is reset. */
    function storeConsent(choice: ConsentChoice): void {
        setCookie('consent', choice);
        consent.value = choice;
        bannerVisible.value = false;
    }

    /** Accept all — update Consent Mode v2, persist, reload for server-side script inclusion. */
    function acceptAll(): void {
        pushConsentUpdate('granted');
        storeConsent('accepted');
        window.location.reload();
    }

    /** Decline all — update Consent Mode v2, persist, reload to keep scripts excluded. */
    function declineAll(): void {
        pushConsentUpdate('denied');
        storeConsent('declined');
        window.location.reload();
    }

    /** Forget the stored choice and bring the banner back. */
    function openCookieSettings(): void {
        clearCookie('consent');
        consent.value = null;
        bannerVisible.value = true;
    }

    return {
        bannerVisible,
        consent,
        checkConsent,
        acceptAll,
        declineAll,
        openCookieSettings,
    };
}
