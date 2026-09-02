import { ref } from 'vue';

export type ConsentChoice = 'accepted' | 'declined';

/**
 * Module-level so the banner (mounted outside the Inertia root), the
 * privacy page's cookie settings and page components like PostAdSlot
 * share one visibility/consent state.
 */
const bannerVisible = ref(false);

/** The stored choice as reactive state ('accepted' | 'declined' | null). */
const consent = ref<ConsentChoice | null>(null);

const getStoredConsent = (): ConsentChoice | null => {
    if (typeof document === 'undefined') {
        return null;
    }

    const match = document.cookie.match(/(?:^|;\s*)consent=([^;]*)/);
    const value = match?.[1];

    return value === 'accepted' || value === 'declined' ? value : null;
};

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

export type UseConsentReturn = {
    bannerVisible: ReturnType<typeof ref<boolean>>;
    consent: ReturnType<typeof ref<ConsentChoice | null>>;
    checkConsent: () => void;
    storeConsent: (choice: ConsentChoice) => void;
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
        storeConsent,
        openCookieSettings,
    };
}
