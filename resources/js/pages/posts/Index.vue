<script setup lang="ts">
import { Head, InfiniteScroll, Link } from '@inertiajs/vue3';
import ContentCard from '@/components/site/ContentCard.vue';
import { ArrowLeft } from '@/components/site/icons';
import { useScrollAnimations } from '@/composables/useScrollAnimations';
import type { PublicPost } from '@/data/portfolio';
import PublicLayout from '@/layouts/PublicLayout.vue';
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

    <PublicLayout>
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
            <div
                class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                data-motion-group
            >
                <ContentCard
                    v-for="post in posts.data"
                    :key="post.id"
                    :href="postShow.url({ post: post.slug })"
                    :cover-url="post.cover_url"
                    :title="post.title"
                    :date="
                        new Date(post.published_at).toLocaleDateString(
                            'en-US',
                            {
                                month: 'short',
                                day: 'numeric',
                                year: 'numeric',
                            },
                        )
                    "
                    :description="post.teaser_text"
                    :tags="post.tags"
                />
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
    </PublicLayout>
</template>
