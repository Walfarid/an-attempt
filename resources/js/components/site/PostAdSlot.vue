<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { useConsent } from '@/composables/useConsent';

const props = defineProps<{
    id: number;
}>();

const el = ref<HTMLElement | null>(null);

/** Empty meaning no ad content or iframe filled the slot (blocked/not served). */
const isEmpty = ref(true);

type Ezstandalone = {
    cmd?: unknown[];
    showAds?: (id: number) => void;
};

let fillObserver: MutationObserver | null = null;
let giveUpTimer: number | null = null;

function stopWatchingForFill(): void {
    fillObserver?.disconnect();
    fillObserver = null;

    if (giveUpTimer !== null) {
        window.clearTimeout(giveUpTimer);
        giveUpTimer = null;
    }
}

/** Ezoic fills the placeholder asynchronously; react the moment content
 * lands instead of guessing how long a fill takes. Give up quietly
 * after 10s — nothing more is coming this pageview. */
function watchForFill(): void {
    const slot = el.value;

    if (!slot || typeof MutationObserver === 'undefined') {
        return;
    }

    stopWatchingForFill();

    const filled = () => Boolean(slot.querySelector('iframe, ins, img'));

    if (filled()) {
        isEmpty.value = false;

        return;
    }

    fillObserver = new MutationObserver(() => {
        if (filled()) {
            isEmpty.value = false;
            stopWatchingForFill();
        }
    });
    fillObserver.observe(slot, { childList: true, subtree: true });
    giveUpTimer = window.setTimeout(stopWatchingForFill, 10_000);
}

/**
 * sa.min.js ships in the document head for full page loads with consent,
 * but an SPA navigation onto the post page never receives it — the head
 * was rendered for the previous page. Inject it in that case, after
 * setting up the command queue the script picks up on load.
 */
function ensureStandaloneScript(): void {
    if (document.querySelector('script[src*="ezojs.com"]')) {
        return;
    }

    const w = window as unknown as { ezstandalone?: Ezstandalone };

    w.ezstandalone ??= {};
    w.ezstandalone.cmd ??= [];

    const script = document.createElement('script');
    script.async = true;
    script.src = '//www.ezojs.com/ezoic/sa.min.js';
    document.head.appendChild(script);
}

function showAd(): void {
    if (!el.value || typeof window === 'undefined') {
        return;
    }

    // Privacy policy: no Ezoic code runs before an explicit accept.
    if (consent.value !== 'accepted') {
        return;
    }

    ensureStandaloneScript();

    const w = window as unknown as { ezstandalone?: Ezstandalone };

    w.ezstandalone?.cmd?.push(() => {
        (
            window as unknown as { ezstandalone?: Ezstandalone }
        ).ezstandalone?.showAds?.(props.id);
    });

    watchForFill();
}

const { consent } = useConsent();

onMounted(showAd);

// Fires when the visitor accepts via the banner (or later through the
// privacy page's cookie settings) so ads can start without a reload.
watch(consent, showAd);

onUnmounted(stopWatchingForFill);
</script>

<template>
    <aside
        ref="el"
        class="ezoic-ad-slot d-surface flex min-h-20 items-center justify-center border border-dashed p-3"
        :data-empty="isEmpty"
    >
        <p v-if="isEmpty" class="text-xs text-(--ink-soft)" data-ad-note>
            Please allow ads to support me
        </p>
        <!--
            Ezoic's injection target: showAds(id) fills
            #ezoic-pub-ad-placeholder-{id}. It must stay unstyled (Ezoic
            docs: styling/reserving space on the placeholder itself causes
            empty gaps) and permanently in the DOM, note or not.
        -->
        <div :id="`ezoic-pub-ad-placeholder-${id}`" />
    </aside>
</template>
