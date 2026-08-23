<script setup lang="ts">
import { ArrowUpRight } from '@lucide/vue';
import type { Profile } from '@/data/portfolio';

withDefaults(
    defineProps<{
        profile?: Pick<Profile, 'github_url' | 'name'> | null;
    }>(),
    {
        profile: null,
    },
);

const year = new Date().getFullYear();
</script>

<template>
    <footer class="border-t-2 border-(--site-ink)">
        <div
            class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-4 px-4 py-8 sm:flex-row sm:items-center sm:px-6"
        >
            <p class="b-label text-(--site-text-secondary)">
                © {{ year }} {{ profile?.name ?? 'Walfa' }} · built with love
            </p>
            <ul class="flex flex-wrap items-center gap-x-5 gap-y-2">
                <li v-if="profile?.github_url">
                    <a
                        :href="profile.github_url"
                        target="_blank"
                        rel="noreferrer noopener"
                        class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold no-underline"
                    >
                        GitHub
                        <ArrowUpRight class="size-3.5" aria-hidden="true" />
                        <span class="sr-only">(opens in a new tab)</span>
                    </a>
                </li>
                <li>
                    <a
                        href="/#contact"
                        class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold no-underline"
                    >
                        Contact
                        <ArrowUpRight class="size-3.5" aria-hidden="true" />
                    </a>
                </li>
            </ul>
        </div>
    </footer>
</template>
