<script setup lang="ts">
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import type { Profile } from '@/data/portfolio';

withDefaults(
    defineProps<{
        mainClass?: string;
        profile?: Pick<Profile, 'github_url' | 'name'> | null;
    }>(),
    {
        mainClass: 'mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24',
        profile: null,
    },
);
</script>

<template>
    <div
        class="site d-dots-bg antialiased selection:bg-(--accent) selection:text-(--paper)"
    >
        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:inline-flex focus:min-h-11 focus:border focus:border-(--ink) focus:bg-(--accent) focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-(--paper)"
        >
            Skip to main content
        </a>

        <SiteHeader />

        <main id="main" :class="mainClass">
            <slot />
        </main>

        <SiteFooter :profile="profile" />
    </div>
</template>
