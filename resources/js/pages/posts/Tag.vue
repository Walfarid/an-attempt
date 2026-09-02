<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@/components/site/icons';
import PostTags from '@/components/site/PostTags.vue';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import { useScrollAnimations } from '@/composables/useScrollAnimations';
import type { PublicPost, PublicTag } from '@/data/portfolio';
import { show as postShow } from '@/routes/posts';

const { tag, posts } = defineProps<{
    tag: PublicTag;
    posts: PublicPost[];
}>();

useScrollAnimations();
</script>

<template>
    <Head :title="`Posts tagged “${tag.name}”`" />

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
                <p class="d-section mb-2">Tag</p>
                <h1
                    class="font-display text-3xl font-bold tracking-tight sm:text-4xl"
                >
                    Posts tagged “{{ tag.name }}”
                </h1>
                <p class="mt-3 text-base leading-relaxed text-(--ink-soft)">
                    {{
                        posts.length === 1
                            ? 'One note so far.'
                            : `${posts.length} notes so far.`
                    }}
                </p>
            </div>

            <div
                v-if="posts.length"
                class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                data-motion-group
            >
                <Link
                    v-for="post in posts"
                    :key="post.id"
                    :href="postShow.url({ post: post.slug })"
                    prefetch
                    cache-for="10s"
                    class="d-surface d-card-hover flex flex-col no-underline"
                    data-motion
                >
                    <img
                        v-if="post.cover_url"
                        :src="post.cover_url"
                        :alt="post.title"
                        loading="lazy"
                        decoding="async"
                        class="aspect-video w-full border-b border-(--rule) object-cover"
                    />

                    <div class="flex flex-1 flex-col gap-2 p-5">
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
                            class="mt-1 line-clamp-2 text-sm leading-relaxed text-(--ink-soft)"
                        >
                            {{ post.teaser_text }}
                        </p>
                        <div class="mt-auto pt-3">
                            <PostTags
                                v-if="post.tags?.length"
                                :tags="post.tags"
                            />
                        </div>
                    </div>
                </Link>
            </div>

            <p
                v-else
                class="d-surface p-8 text-center leading-relaxed text-(--ink-soft)"
            >
                Nothing published under this tag yet.
            </p>

            <Link
                href="/posts"
                class="mt-10 inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold no-underline"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                All posts
            </Link>
        </main>

        <SiteFooter />
    </div>
</template>
