<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useConsent } from '@/composables/useConsent';
import { useScrollAnimations } from '@/composables/useScrollAnimations';
import PublicLayout from '@/layouts/PublicLayout.vue';

const { policy } = defineProps<{
    policy: { body_html: string; updated_at: string };
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

    <PublicLayout>
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
    </PublicLayout>
</template>
