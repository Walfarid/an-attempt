<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowUpRight } from '@lucide/vue';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import { useScrollAnimations } from '@/composables/useScrollAnimations';
import type { PublicPostDetail } from '@/data/portfolio';
import { show as postShow } from '@/routes/posts';

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

useScrollAnimations();
</script>

<template>
    <Head :title="post.title" />

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

        <main id="main" class="mx-auto max-w-3xl px-4 py-16 sm:px-6 sm:py-24">
            <article data-motion>
                <time class="d-label">
                    {{ formatPublished(post.published_at) }}
                </time>
                <h1
                    class="mt-3 font-display text-[clamp(1.9rem,4.5vw,3rem)] leading-[1.08] font-bold tracking-tight text-balance"
                >
                    {{ post.title }}
                </h1>

                <img
                    v-if="post.cover_url"
                    :src="post.cover_url"
                    :alt="post.title"
                    class="mt-8 aspect-video w-full border border-(--rule) object-cover"
                />

                <!-- eslint-disable-next-line vue/no-v-html — server-rendered sanitized Markdown -->
                <div
                    class="prose-site mt-8 leading-relaxed"
                    v-html="post.body_html"
                />
            </article>

            <aside
                v-if="recent.length"
                class="mx-auto mt-16 max-w-3xl border-t border-(--rule) pt-8"
            >
                <p class="d-label mb-4">Keep reading</p>
                <ul class="space-y-2">
                    <li v-for="item in recent" :key="item.id">
                        <Link
                            :href="postShow.url({ post: item.slug })"
                            class="inline-flex min-h-11 items-center gap-1.5 font-semibold no-underline transition-colors hover:text-(--accent)"
                        >
                            {{ item.title }}
                            <ArrowUpRight
                                class="size-4 shrink-0"
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
