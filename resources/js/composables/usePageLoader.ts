/**
 * usePageLoader — orchestrates the self-drawing page loader state.
 *
 * Boot sequence: the loader starts visible; `page-loader:boot-complete`
 * (dispatched by the Inertia setup) signals the page is rendered, ending
 * the boot.
 *
 * SPA sequence: `inertia:start` / `inertia:finish` drive the lightweight
 * top bar, and `inertia:progress` drives its fill.
 */
import { ref, onMounted, onBeforeUnmount } from 'vue';

export function usePageLoader() {
    const progress = ref(0);
    const isSpaActive = ref(false);
    const isBoot = ref(true);
    const hasCompletedBoot = ref(false);

    let progressHandler: ((event: Event) => void) | null = null;
    let startHandler: (() => void) | null = null;
    let finishHandler: (() => void) | null = null;
    let bootCompleteHandler: (() => void) | null = null;
    let bootCapTimer: number | null = null;

    function onProgress(event: Event) {
        const detail = (event as CustomEvent).detail;
        const percentage = detail?.progress?.percentage;

        if (percentage !== undefined) {
            progress.value = Math.max(progress.value, percentage / 100);
        }
    }

    function onSpaStart() {
        isSpaActive.value = true;
        progress.value = 0;
    }

    function onSpaFinish() {
        progress.value = 1;
        // Give the bar a beat to fill, then drop it.
        window.setTimeout(() => {
            isSpaActive.value = false;
            progress.value = 0;
        }, 350);
    }

    function completeBoot() {
        if (!isBoot.value) {
            return;
        }

        isBoot.value = false;
        hasCompletedBoot.value = true;

        if (bootCapTimer) {
            window.clearTimeout(bootCapTimer);
            bootCapTimer = null;
        }
    }

    onMounted(() => {
        progressHandler = onProgress;
        startHandler = onSpaStart;
        finishHandler = onSpaFinish;
        bootCompleteHandler = completeBoot;

        document.addEventListener('inertia:start', startHandler);
        document.addEventListener('inertia:progress', progressHandler);
        document.addEventListener('inertia:finish', finishHandler);
        window.addEventListener('page-loader:boot-complete', bootCompleteHandler);

        // Hard cap: if boot-complete never fires, end the boot anyway.
        bootCapTimer = window.setTimeout(completeBoot, 3000);
    });

    onBeforeUnmount(() => {
        if (progressHandler) {
document.removeEventListener('inertia:progress', progressHandler);
}

        if (startHandler) {
document.removeEventListener('inertia:start', startHandler);
}

        if (finishHandler) {
document.removeEventListener('inertia:finish', finishHandler);
}

        if (bootCompleteHandler) {
window.removeEventListener('page-loader:boot-complete', bootCompleteHandler);
}

        if (bootCapTimer) {
            window.clearTimeout(bootCapTimer);
            bootCapTimer = null;
        }
    });

    return {
        progress,
        isSpaActive,
        isBoot,
        hasCompletedBoot,
        completeBoot,
    };
}
