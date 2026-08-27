<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import { useConsent } from '@/composables/useConsent';
import { useScrollAnimations } from '@/composables/useScrollAnimations';

const { policy } = defineProps<{
    policy: { id: number; body_html: string; updated_at: string };
}>();

const { openCookieSettings } = useConsent();

useScrollAnimations();

function formatUpdated(iso: string): string {
    return new Date(iso).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Privacy" />

    <div
        class="site d-dots-bg min-h-dvh antialiased selection:bg-(--accent) selection:text-(--paper)"
    >
        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:inline-flex focus:min-h-11 focus:border focus:border-(--ink) focus:bg-(--accent) focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-(--paper)"
        >
            Skip to main content
        </a>

        <SiteHeader />

        <main id="main" class="mx-auto max-w-3xl px-4 py-16 sm:px-6 sm:py-20">
            <div class="mb-8" data-motion>
                <p class="d-section mb-2">Legal</p>
                <h1
                    class="font-display text-3xl font-bold tracking-tight sm:text-4xl"
                >
                    Privacy
                </h1>
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <p class="text-sm text-(--ink-soft)">
                        Last updated
                        {{ formatUpdated(policy.updated_at) }}
                    </p>
                    <button
                        type="button"
                        class="d-press inline-flex min-h-9 items-center border border-(--rule) bg-(--surface) px-3 py-1 text-xs font-semibold transition-colors hover:bg-(--accent-soft)"
                        @click="openCookieSettings"
                    >
                        Cookie settings
                    </button>
                </div>
            </div>

            <!-- eslint-disable-next-line vue/no-v-html — server-rendered sanitized Markdown -->
            <div
                class="prose-site leading-relaxed"
                data-motion
                v-html="policy.body_html"
            />
        </main>

        <SiteFooter />
    </div>
</template>
