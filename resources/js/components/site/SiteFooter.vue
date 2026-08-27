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

function scrollToTop(e: Event) {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<template>
    <footer class="border-t border-(--rule)">
        <div
            class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-6 px-4 py-10 sm:flex-row sm:items-center sm:px-6"
        >
            <div>
                <p class="text-sm font-medium">
                    © {{ year }} {{ profile?.name ?? 'Walfa' }}
                </p>
                <p class="mt-1 text-xs text-(--ink-soft)">
                    Built with Laravel, Vue, and way too much coffee.
                </p>
            </div>
            <ul class="flex flex-wrap items-center gap-x-5 gap-y-2">
                <li v-if="profile?.github_url">
                    <a
                        :href="profile.github_url"
                        target="_blank"
                        rel="noreferrer noopener"
                        class="inline-flex min-h-10 items-center gap-1 text-sm font-medium text-(--ink-soft) no-underline transition-colors hover:text-(--ink)"
                    >
                        GitHub
                        <ArrowUpRight class="size-3.5" aria-hidden="true" />
                        <span class="sr-only">(opens in a new tab)</span>
                    </a>
                </li>
                <li>
                    <a
                        href="/#contact"
                        class="inline-flex min-h-10 items-center gap-1 text-sm font-medium text-(--ink-soft) no-underline transition-colors hover:text-(--ink)"
                    >
                        Contact
                        <ArrowUpRight class="size-3.5" aria-hidden="true" />
                    </a>
                </li>
                <li>
                    <a
                        href="/privacy"
                        class="inline-flex min-h-10 items-center gap-1 text-sm font-medium text-(--ink-soft) no-underline transition-colors hover:text-(--ink)"
                    >
                        Privacy
                    </a>
                </li>
                <li>
                    <a
                        href="#"
                        class="inline-flex min-h-10 items-center text-sm font-medium text-(--ink-soft) no-underline transition-colors hover:text-(--ink)"
                        @click="scrollToTop"
                    >
                        Back to top ↑
                    </a>
                </li>
            </ul>
        </div>
    </footer>
</template>
