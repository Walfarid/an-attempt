<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowUpRight } from '@lucide/vue';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
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
</script>

<template>
    <Head :title="post.title" />

    <div
        class="site min-h-dvh antialiased selection:bg-(--site-accent) selection:text-(--site-on-accent)"
    >
        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:inline-flex focus:min-h-11 focus:border-2 focus:border-(--site-ink) focus:bg-(--site-accent) focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-(--site-on-accent)"
        >
            Skip to main content
        </a>

        <SiteHeader />

        <main id="main" class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
            <article class="mx-auto max-w-3xl">
                <time class="b-label text-(--site-text-secondary)">
                    {{ formatPublished(post.published_at) }}
                </time>
                <h1
                    class="mt-3 text-[clamp(1.9rem,4.5vw,3rem)] leading-[1.08] font-bold tracking-tight text-balance"
                >
                    {{ post.title }}
                </h1>

                <img
                    v-if="post.cover_url"
                    :src="post.cover_url"
                    alt=""
                    class="mt-8 aspect-video w-full border-2 border-(--site-ink) object-cover"
                />

                <!-- eslint-disable-next-line vue/no-v-html — server-rendered sanitized Markdown -->
                <div
                    class="prose-site mt-8 leading-relaxed text-(--site-text)"
                    v-html="post.body_html"
                />
            </article>

            <aside
                v-if="recent.length"
                class="mx-auto mt-16 max-w-3xl border-t-2 border-(--site-ink) pt-8"
            >
                <p class="b-label mb-4 text-(--site-text-secondary)">
                    Keep reading
                </p>
                <ul class="space-y-2">
                    <li v-for="item in recent" :key="item.id">
                        <Link
                            :href="postShow.url({ post: item.slug })"
                            class="inline-flex min-h-11 items-center gap-1.5 font-semibold no-underline hover:text-(--site-accent)"
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
