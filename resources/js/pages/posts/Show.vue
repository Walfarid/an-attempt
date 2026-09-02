<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { ArrowLeft, ArrowUpRight } from '@/components/site/icons';
import PostAdSlot from '@/components/site/PostAdSlot.vue';
import PostTags from '@/components/site/PostTags.vue';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import { useScrollAnimations } from '@/composables/useScrollAnimations';
import type { PublicPostDetail } from '@/data/portfolio';
import { show as postShow } from '@/routes/posts';

const { post, recent } = defineProps<{
    post: PublicPostDetail;
    recent: { id: number; slug: string; title: string }[];
}>();

const { ezoic_enabled: ezoicEnabled, ezoic_placeholder_id: placeholderId } =
    usePage().props as {
        ezoic_enabled?: boolean;
        ezoic_placeholder_id?: number | string;
    };

/** Wide-screen side-column ad; hidden on narrow screens (inline slot takes over). */
const gutterAd = computed(() =>
    ezoicEnabled
        ? {
              id: Number(placeholderId ?? 101),
              className: 'ezoic-gutter',
          }
        : null,
);

/** Narrow-screen ad at the end of the article. */
const inlineAd = computed(() =>
    ezoicEnabled ? { id: Number(placeholderId ?? 101) } : null,
);

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
            <!--
                Wide screens (xl+): the article is centered and the ad sits
                in a sticky right-hand gutter (post-layout CSS).
                Narrow screens: only the inline slot below the article shows.
            -->
            <div class="post-layout">
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

                <!-- Wide screens: sticky side gutter to the right of the article. -->
                <PostAdSlot
                    v-if="gutterAd"
                    :id="gutterAd.id"
                    :class-name="gutterAd.className"
                />
            </div>

            <!-- Narrow screens: the ad sits at the end of the article (outside
             the post-layout grid, so it stays visible below xl). -->
            <PostAdSlot
                v-if="inlineAd"
                :id="inlineAd.id"
                class="mx-auto mt-10 max-w-3xl xl:hidden"
            />

            <aside
                v-if="recent.length"
                class="mx-auto mt-16 max-w-3xl border-t border-(--rule) pt-8"
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

            <div class="mx-auto mt-10 max-w-3xl">
                <Link
                    href="/writing"
                    class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold no-underline"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    All posts
                </Link>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>
