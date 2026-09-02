<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { ArrowLeft, ArrowUpRight } from '@/components/site/icons';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import { useScrollAnimations } from '@/composables/useScrollAnimations';
import type { PublicGuideDetail } from '@/data/portfolio';
import { index as guideIndex } from '@/routes/guides';
import { show as postShow } from '@/routes/posts';

const { guide } = defineProps<{
    guide: PublicGuideDetail;
}>();

function formatPublished(iso: string): string {
    return new Date(iso).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

/* Reading progress hairline ------------------------------------------ */

const progressBar = ref<HTMLElement | null>(null);
let ticking = false;

function updateProgress() {
    const bar = progressBar.value;

    if (!bar || ticking) {
        return;
    }

    ticking = true;

    requestAnimationFrame(() => {
        const doc = document.documentElement;
        const max = doc.scrollHeight - window.innerHeight;
        const ratio = max > 0 ? Math.min(1, window.scrollY / max) : 0;

        bar.style.transform = `scaleX(${ratio})`;
        // Only visible once the reader is past the top of the page.
        bar.style.opacity = ratio > 0.02 ? '1' : '0';
        ticking = false;
    });
}

onMounted(() => {
    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
});

onUnmounted(() => {
    window.removeEventListener('scroll', updateProgress);
});

useScrollAnimations();
</script>

<template>
    <Head :title="guide.title" />

    <div
        class="site d-dots-bg min-h-dvh antialiased selection:bg-(--accent) selection:text-(--paper)"
    >
        <!-- Reading progress hairline -->
        <div
            ref="progressBar"
            aria-hidden="true"
            class="pointer-events-none fixed inset-x-0 top-0 z-[9997] h-[2px] origin-left bg-(--accent) opacity-0"
            style="transform: scaleX(0)"
        />

        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:inline-flex focus:min-h-11 focus:border focus:border-(--ink) focus:bg-(--accent) focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-(--paper)"
        >
            Skip to main content
        </a>

        <SiteHeader />

        <main id="main" class="px-4 py-16 sm:px-6 sm:py-24">
            <div class="post-layout">
                <article data-motion class="w-full max-w-3xl">
                    <time class="d-label">
                        {{ formatPublished(guide.published_at) }}
                    </time>
                    <h1
                        class="mt-3 font-display text-[clamp(1.9rem,4.5vw,3rem)] leading-[1.08] font-bold tracking-tight text-balance"
                    >
                        {{ guide.title }}
                    </h1>

                    <div
                        v-if="guide.estimated_time || guide.prerequisites"
                        class="mt-6 grid gap-3 rounded-md border border-(--rule) bg-(--surface) p-4 text-sm leading-relaxed sm:grid-cols-2"
                    >
                        <div v-if="guide.estimated_time">
                            <p class="d-label mb-1">Time</p>
                            <p class="text-(--ink-soft)">
                                {{ guide.estimated_time }}
                            </p>
                        </div>
                        <div v-if="guide.prerequisites">
                            <p class="d-label mb-1">Prerequisites</p>
                            <p class="whitespace-pre-line text-(--ink-soft)">
                                {{ guide.prerequisites }}
                            </p>
                        </div>
                    </div>

                    <img
                        v-if="guide.cover_url"
                        :src="guide.cover_url"
                        :alt="guide.title"
                        decoding="async"
                        class="mt-8 aspect-video w-full border border-(--rule) object-cover"
                    />

                    <!-- eslint-disable-next-line vue/no-v-html — server-rendered sanitized Markdown -->
                    <div
                        class="prose-site mt-8 leading-relaxed"
                        v-html="guide.body_html"
                    />
                </article>

                <aside
                    v-if="guide.posts.length"
                    class="mt-16 border-t border-(--rule) pt-8"
                >
                    <p class="d-label mb-4">In this guide</p>
                    <ul class="space-y-2">
                        <li v-for="post in guide.posts" :key="post.id">
                            <Link
                                :href="postShow.url({ post: post.slug })"
                                prefetch
                                cache-for="10s"
                                class="d-arrow-link inline-flex min-h-11 items-center gap-1.5 font-semibold no-underline transition-colors hover:text-(--accent)"
                            >
                                {{ post.title }}
                                <ArrowUpRight
                                    class="d-arrow-icon size-4 shrink-0"
                                    aria-hidden="true"
                                />
                            </Link>
                        </li>
                    </ul>
                </aside>

                <div class="mt-10">
                    <Link
                        :href="guideIndex.url()"
                        class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold no-underline"
                    >
                        <ArrowLeft class="size-4" aria-hidden="true" />
                        All guides
                    </Link>
                </div>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>
