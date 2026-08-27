import { ref } from 'vue';

export type ConsentChoice = 'accepted' | 'declined';

/**
 * Module-level so the banner (mounted outside the Inertia root) and the
 * "Cookie settings" button on the privacy page share one visibility state.
 */
const bannerVisible = ref(false);

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
    checkConsent: () => void;
    storeConsent: (choice: ConsentChoice) => void;
    openCookieSettings: () => void;
};

export function useConsent(): UseConsentReturn {
    /** Show the banner only while no stored choice exists. */
    function checkConsent(): void {
        bannerVisible.value = getStoredConsent() === null;
    }

    /** Persist the choice; the banner hides for good until it is reset. */
    function storeConsent(choice: ConsentChoice): void {
        setCookie('consent', choice);
        bannerVisible.value = false;
    }

    /** Forget the stored choice and bring the banner back. */
    function openCookieSettings(): void {
        clearCookie('consent');
        bannerVisible.value = true;
    }

    return { bannerVisible, checkConsent, storeConsent, openCookieSettings };
}
