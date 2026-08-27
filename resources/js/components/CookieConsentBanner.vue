<script setup lang="ts">
import { onMounted } from 'vue';
import { privacy } from '@/routes';
import { useConsent } from '@/composables/useConsent';
import { Button } from '@/components/ui/button';

const { bannerVisible, checkConsent, storeConsent } = useConsent();

onMounted(checkConsent);
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
                    :href="privacy()"
                    class="font-medium text-(--ink) underline underline-offset-2 hover:text-(--accent)"
                    >Read the disclosure</a
                >.
            </p>
            <div class="flex shrink-0 items-center gap-2">
                <Button
                    size="sm"
                    class="d-sharp bg-(--accent) text-(--paper) hover:bg-(--accent-hover)"
                    @click="storeConsent('accepted')"
                >
                    Accept
                </Button>
                <Button
                    size="sm"
                    variant="outline"
                    class="d-sharp"
                    @click="storeConsent('declined')"
                >
                    Decline
                </Button>
            </div>
        </div>
    </div>
</template>
