<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ContentCard from '@/components/site/ContentCard.vue';
import { ArrowLeft } from '@/components/site/icons';
import { useScrollAnimations } from '@/composables/useScrollAnimations';
import type { PublicPost, PublicTag } from '@/data/portfolio';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { show as postShow } from '@/routes/posts';

const { tag, posts } = defineProps<{
    tag: PublicTag;
    posts: PublicPost[];
}>();

useScrollAnimations();
</script>

<template>
    <Head :title="`Posts tagged “${tag.name}”`" />

    <PublicLayout>
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
            <ContentCard
                v-for="post in posts"
                :key="post.id"
                :href="postShow.url({ post: post.slug })"
                :cover-url="post.cover_url"
                :title="post.title"
                :date="
                    new Date(post.published_at).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                    })
                "
                :description="post.teaser_text"
                :tags="post.tags"
            />
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
    </PublicLayout>
</template>
