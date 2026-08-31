<script setup lang="ts">
import { onMounted } from 'vue';
import { useConsent } from '@/composables/useConsent';
import { privacy } from '@/routes';

const { bannerVisible, checkConsent, storeConsent } = useConsent();

onMounted(checkConsent);

// Plain <button>s instead of the ui/button component: Button pulls reka-ui
// (Primitive) + the utils chunk (cva/cn) into the eager boot graph, which
// would force 150+ KB of dashboard-only UI code onto every public page.
// Classes mirror buttonVariants('sm') exactly (svg/aria-invalid/disabled
// rules omitted — the banner buttons never have icons or those states).
const acceptClasses =
    'd-sharp inline-flex h-8 shrink-0 items-center justify-center gap-1.5 whitespace-nowrap px-3 font-mono text-sm font-medium transition-colors outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] bg-(--accent) text-(--paper) hover:bg-(--accent-hover)';
const declineClasses =
    'd-sharp inline-flex h-8 shrink-0 items-center justify-center gap-1.5 whitespace-nowrap border border-(--rule) bg-(--paper) px-3 font-mono text-sm font-medium transition-colors outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] hover:bg-(--accent) dark:bg-input/30 dark:hover:bg-input/50';
</script>

<template>
    <div
        v-if="bannerVisible"
        role="region"
        aria-label="Cookie consent"
        class="fixed inset-x-0 bottom-0 z-50 flex justify-center px-3 pb-3"
        style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))"
    >
        <div
            class="flex w-full max-w-3xl flex-wrap items-center justify-between gap-x-4 gap-y-2 border border-(--rule) bg-(--paper) px-4 py-2.5 shadow-lg shadow-black/5"
        >
            <p class="text-sm leading-snug text-(--ink-soft)">
                I use privacy-friendly analytics
                <span class="text-(--ink)">(Clarity, GA4)</span>
                to understand how the site is used.
                <a
                    :href="privacy.url()"
                    class="font-medium text-(--ink) underline underline-offset-2 hover:text-(--accent)"
                    >Read the disclosure</a
                >.
            </p>
            <div class="flex shrink-0 items-center gap-2">
                <button
                    type="button"
                    :class="acceptClasses"
                    @click="storeConsent('accepted')"
                >
                    Accept
                </button>
                <button
                    type="button"
                    :class="declineClasses"
                    @click="storeConsent('declined')"
                >
                    Decline
                </button>
            </div>
        </div>
    </div>
</template>
