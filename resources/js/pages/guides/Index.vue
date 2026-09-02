<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@/components/site/icons';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import { useScrollAnimations } from '@/composables/useScrollAnimations';
import type { PublicGuide } from '@/data/portfolio';
import { show as guideShow } from '@/routes/guides';

defineProps<{
    guides: {
        data: PublicGuide[];
        next_page_url: string | null;
    };
}>();

useScrollAnimations();

function formatPublished(iso: string): string {
    return new Date(iso).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Guides" />

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

        <main id="main" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
            <div class="mb-10" data-motion>
                <p class="d-section mb-2">Guides</p>
                <h1
                    class="font-display text-3xl font-bold tracking-tight sm:text-4xl"
                >
                    Step-by-step guides
                </h1>
                <p
                    class="mt-3 max-w-2xl text-base leading-relaxed text-(--ink-soft)"
                >
                    Longer, structured walkthroughs — each one links the posts
                    it builds on.
                </p>
            </div>

            <div
                v-if="guides.data.length"
                class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                data-motion-group
            >
                <Link
                    v-for="guide in guides.data"
                    :key="guide.slug"
                    :href="guideShow.url({ guide: guide.slug })"
                    prefetch
                    cache-for="10s"
                    class="d-surface d-card-hover flex flex-col no-underline"
                    data-motion
                >
                    <img
                        v-if="guide.cover_url"
                        :src="guide.cover_url"
                        :alt="guide.title"
                        loading="lazy"
                        decoding="async"
                        class="aspect-video w-full border-b border-(--rule) object-cover"
                    />

                    <div class="flex flex-1 flex-col gap-2 p-5">
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="font-display text-lg font-semibold">
                                {{ guide.title }}
                            </p>
                        </div>

                        <p
                            v-if="guide.estimated_time"
                            class="d-label inline-flex w-fit items-center rounded-full border border-(--rule) px-2 py-0.5"
                        >
                            {{ guide.estimated_time }}
                        </p>

                        <p
                            v-if="guide.teaser"
                            class="mt-1 line-clamp-3 text-sm leading-relaxed text-(--ink-soft)"
                        >
                            {{ guide.teaser }}
                        </p>

                        <time class="d-label mt-auto pt-3">
                            {{ formatPublished(guide.published_at) }}
                        </time>
                    </div>
                </Link>
            </div>

            <p
                v-else
                class="d-surface p-8 text-center leading-relaxed text-(--ink-soft)"
            >
                Nothing published yet — check back soon.
            </p>

            <Link
                href="/"
                class="mt-10 inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold no-underline"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Back home
            </Link>
        </main>

        <SiteFooter />
    </div>
</template>
