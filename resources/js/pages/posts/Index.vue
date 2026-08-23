<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowUpRight } from '@lucide/vue';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import type { PublicPost } from '@/data/portfolio';
import { show as postShow } from '@/routes/posts';

defineProps<{
    posts: PublicPost[];
}>();
</script>

<template>
    <Head title="Writing" />

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
            <div class="mb-10 max-w-2xl">
                <p class="b-label mb-2 text-(--site-accent)">Writing</p>
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    Notes &amp; ramblings
                </h1>
            </div>

            <ol v-if="posts.length" class="space-y-4">
                <li v-for="post in posts" :key="post.id">
                    <Link
                        :href="postShow.url({ post: post.slug })"
                        class="b-panel b-shadow-sm b-lift grid gap-6 p-6 no-underline sm:grid-cols-[auto_1fr] sm:p-8"
                    >
                        <img
                            v-if="post.cover_url"
                            :src="post.cover_url"
                            alt=""
                            class="aspect-video w-full border-2 border-(--site-ink) object-cover sm:w-48"
                        />
                        <div class="min-w-0">
                            <time class="b-label text-(--site-text-secondary)">
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
                            <h2 class="mt-2 text-xl font-bold tracking-tight">
                                {{ post.title }}
                            </h2>
                            <p
                                class="mt-2 line-clamp-2 leading-relaxed text-(--site-text-secondary)"
                            >
                                {{ post.teaser_text ?? post.excerpt }}
                            </p>
                            <span
                                class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-(--site-accent)"
                                >Read
                                <ArrowUpRight
                                    class="size-3.5"
                                    aria-hidden="true"
                            /></span>
                        </div>
                    </Link>
                </li>
            </ol>

            <p
                v-else
                class="b-panel b-shadow-sm p-8 text-center leading-relaxed text-(--site-text-secondary)"
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
