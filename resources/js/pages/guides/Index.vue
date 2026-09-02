<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ContentCard from '@/components/site/ContentCard.vue';
import { ArrowLeft } from '@/components/site/icons';
import { useScrollAnimations } from '@/composables/useScrollAnimations';
import type { PublicGuide } from '@/data/portfolio';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatDate } from '@/lib/utils';
import { show as guideShow } from '@/routes/guides';

defineProps<{
    guides: {
        data: PublicGuide[];
        next_page_url: string | null;
    };
}>();

useScrollAnimations();

function formatPublished(iso: string): string {
    return formatDate(iso);
}
</script>

<template>
    <Head title="Guides" />

    <PublicLayout>
        <div class="mb-10" data-motion>
            <p class="d-section mb-2">Guides</p>
            <h1
                class="font-display text-3xl font-bold tracking-tight sm:text-4xl"
            >
                Step-by-step guides
            </h1>
            <p
                class="mt-3 max-w-2xl text-base leading-relaxed text-(--ink-soft)"
            >
                Longer, structured walkthroughs — each one links the posts it
                builds on.
            </p>
        </div>

        <div
            v-if="guides.data.length"
            class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
            data-motion-group
        >
            <ContentCard
                v-for="guide in guides.data"
                :key="guide.slug"
                :href="guideShow.url({ guide: guide.slug })"
                :cover-url="guide.cover_url"
                :title="guide.title"
                :date="formatPublished(guide.published_at)"
                :description="guide.teaser ?? undefined"
                title-tag="p"
                date-position="bottom"
                :estimated-time="guide.estimated_time ?? undefined"
                description-class="mt-1 line-clamp-3 text-sm leading-relaxed text-(--ink-soft)"
            />
        </div>

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
