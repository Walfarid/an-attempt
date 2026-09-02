import type { Auth } from '@/types/auth';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen?: boolean;
            adsenseClientId?: string;
            adsenseSlotId?: string;
            [key: string]: unknown;
        };
    }
}

declare global {
    interface Window {
        gtag?: (
            command: string,
            action: string,
            params?: Record<string, string>,
        ) => void;
        adsbygoogle?: Record<string, unknown>[];
    }
}

export {};
