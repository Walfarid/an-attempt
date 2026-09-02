<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PostTags from '@/components/site/PostTags.vue';
import type { PublicTag } from '@/data/portfolio';

withDefaults(
    defineProps<{
        href: string;
        coverUrl?: string | null;
        title: string;
        date: string;
        description?: string;
        tags?: PublicTag[];
        titleTag?: 'h2' | 'h3' | 'p';
        titleClass?: string;
        estimatedTime?: string;
        datePosition?: 'row' | 'bottom';
        descriptionClass?: string;
        compact?: boolean;
    }>(),
    {
        coverUrl: null,
        description: '',
        tags: undefined,
        titleTag: 'h2',
        titleClass: 'text-lg',
        estimatedTime: undefined,
        datePosition: 'row',
        descriptionClass:
            'mt-1 line-clamp-2 text-sm leading-relaxed text-(--ink-soft)',
        compact: false,
    },
);
</script>

<template>
    <Link
        :href="href"
        prefetch
        cache-for="10s"
        :class="
            compact
                ? 'd-surface d-card-hover block p-5 no-underline sm:p-6'
                : 'd-surface d-card-hover flex flex-col no-underline'
        "
        data-motion
    >
        <img
            v-if="coverUrl && !compact"
            :src="coverUrl"
            :alt="title"
            loading="lazy"
            decoding="async"
            class="aspect-video w-full border-b border-(--rule) object-cover"
        />

        <div v-if="!compact" class="flex flex-1 flex-col gap-2 p-5">
            <div
                v-if="datePosition === 'row'"
                class="flex flex-col gap-2 sm:flex-row sm:items-baseline sm:justify-between"
            >
                <component
                    :is="titleTag"
                    class="font-display font-semibold"
                    :class="titleClass"
                >
                    {{ title }}
                </component>
                <time class="d-label shrink-0">{{ date }}</time>
            </div>
            <div v-else class="flex items-baseline justify-between gap-3">
                <component
                    :is="titleTag"
                    class="font-display font-semibold"
                    :class="titleClass"
                >
                    {{ title }}
                </component>
            </div>

            <p
                v-if="estimatedTime"
                class="d-label inline-flex w-fit items-center rounded-full border border-(--rule) px-2 py-0.5"
            >
                {{ estimatedTime }}
            </p>

            <p v-if="description" :class="descriptionClass">
                {{ description }}
            </p>

            <time v-if="datePosition === 'bottom'" class="d-label mt-auto pt-3">
                {{ date }}
            </time>

            <div v-if="tags && tags.length" class="mt-auto pt-3">
                <PostTags :tags="tags" />
            </div>
        </div>

        <template v-else>
            <div
                class="flex flex-col gap-2 sm:flex-row sm:items-baseline sm:justify-between"
            >
                <component
                    :is="titleTag"
                    class="font-display font-semibold"
                    :class="titleClass"
                >
                    {{ title }}
                </component>
                <time class="d-label shrink-0">{{ date }}</time>
            </div>
            <p v-if="description" :class="descriptionClass">
                {{ description }}
            </p>
        </template>
    </Link>
</template>
