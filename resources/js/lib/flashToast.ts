import { router } from '@inertiajs/vue3';
import type { FlashToast } from '@/types/ui';

/**
 * Flash toasts are consumed only when a flash payload arrives, and they
 * render only once the dashboard's <Toaster> is mounted — so vue-sonner
 * is imported lazily instead of at boot. This keeps the toast runtime
 * off the public critical path (it is also statically imported by the
 * dashboard layout's Sonner wrapper, so dashboard pages load it anyway).
 */
let toastApi: typeof import('vue-sonner')['toast'] | null = null;

async function showToast(data: FlashToast): Promise<void> {
    toastApi ??= (await import('vue-sonner')).toast;

    toastApi[data.type](data.message);
}

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        void showToast(data);
    });
}