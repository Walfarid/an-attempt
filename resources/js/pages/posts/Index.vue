<script setup lang="ts">
import { Head, InfiniteScroll, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import { useScrollAnimations } from '@/composables/useScrollAnimations';
import type { PublicPost } from '@/data/portfolio';
import { show as postShow } from '@/routes/posts';

defineProps<{
    posts: {
        data: PublicPost[];
        next_page_url: string | null;
    };
}>();

useScrollAnimations();
</script>

<template>
    <Head title="Writing" />

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
            <div class="mb-10" data-motion>
                <p class="d-section mb-2">Writing</p>
                <h1
                    class="font-display text-3xl font-bold tracking-tight sm:text-4xl"
                >
                    Notes &amp; ramblings
                </h1>
                <p class="mt-3 text-base leading-relaxed text-(--ink-soft)">
                    Thoughts on code, tools, and things I'm learning. Irregular
                    cadence, honest opinions.
                </p>
            </div>

            <InfiniteScroll v-if="posts.data.length" data="posts">
                <div class="space-y-3" data-motion-group>
                    <Link
                        v-for="post in posts.data"
                        :key="post.id"
                        :href="postShow.url({ post: post.slug })"
                        prefetch
                        cache-for="10s"
                        class="d-surface d-card-hover block p-5 no-underline sm:p-6"
                        data-motion
                    >
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-baseline sm:justify-between"
                        >
                            <h2 class="font-display text-lg font-semibold">
                                {{ post.title }}
                            </h2>
                            <time class="d-label shrink-0">
                                {{
                                    new Date(
                                        post.published_at,
                                    ).toLocaleDateString('en-US', {
                                        month: 'short',
                                        day: 'numeric',
                                        year: 'numeric',
                                    })
                                }}
                            </time>
                        </div>
                        <p
                            class="mt-2 line-clamp-2 text-sm leading-relaxed text-(--ink-soft)"
                        >
                            {{ post.teaser_text ?? post.excerpt }}
                        </p>
                    </Link>
                </div>
            </InfiniteScroll>

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
