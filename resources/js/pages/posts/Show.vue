<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { ArrowLeft, ArrowUpRight } from '@/components/site/icons';
import AdSlot from '@/components/site/AdSlot.vue';
import PostTags from '@/components/site/PostTags.vue';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import { useScrollAnimations } from '@/composables/useScrollAnimations';
import type { PublicPostDetail } from '@/data/portfolio';
import { index as postIndex, show as postShow } from '@/routes/posts';

const { post, recent } = defineProps<{
    post: PublicPostDetail;
    recent: { id: number; slug: string; title: string }[];
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
    <Head :title="post.title" />

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
                <div class="post-with-ad">
                    <article data-motion class="w-full max-w-3xl">
                        <time class="d-label">
                            {{ formatPublished(post.published_at) }}
                        </time>
                        <h1
                            class="mt-3 font-display text-[clamp(1.9rem,4.5vw,3rem)] leading-[1.08] font-bold tracking-tight text-balance"
                        >
                            {{ post.title }}
                        </h1>

                        <PostTags
                            v-if="post.tags?.length"
                            :tags="post.tags"
                            class="mt-5"
                        />

                        <img
                            v-if="post.cover_url"
                            :src="post.cover_url"
                            :alt="post.title"
                            decoding="async"
                            class="mt-8 aspect-video w-full border border-(--rule) object-cover"
                        />

                        <!-- eslint-disable-next-line vue/no-v-html — server-rendered sanitized Markdown -->
                        <div
                            class="prose-site mt-8 leading-relaxed"
                            v-html="post.body_html"
                        />
                    </article>

                    <div class="mt-8 lg:mt-0">
                        <div class="lg:sticky lg:top-20">
                            <AdSlot />
                        </div>
                    </div>
                </div>

                <aside
                    v-if="recent.length"
                    class="mt-16 border-t border-(--rule) pt-8"
                >
                    <p class="d-label mb-4">Keep reading</p>
                    <ul class="space-y-2">
                        <li v-for="item in recent" :key="item.id">
                            <Link
                                :href="postShow.url({ post: item.slug })"
                                prefetch
                                cache-for="10s"
                                class="d-arrow-link inline-flex min-h-11 items-center gap-1.5 font-semibold no-underline transition-colors hover:text-(--accent)"
                            >
                                {{ item.title }}
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
                        :href="postIndex.url()"
                        class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold no-underline"
                    >
                        <ArrowLeft class="size-4" aria-hidden="true" />
                        All posts
                    </Link>
                </div>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>
