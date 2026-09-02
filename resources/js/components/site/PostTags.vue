<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PublicTag } from '@/data/portfolio';
import { tag as tagShow } from '@/routes/posts';

/**
 * Tag pills for the public post pages. Links to the per-tag landing
 * page so crawlers see real internal anchors.
 */
defineProps<{ tags: PublicTag[] }>();
</script>

<template>
    <ul v-if="tags.length" class="flex flex-wrap gap-2" aria-label="Tags">
        <li v-for="tag in tags" :key="tag.id">
            <Link
                :href="tagShow.url({ tag: tag.slug })"
                prefetch
                cache-for="10s"
                class="inline-flex min-h-8 items-center border border-(--rule) px-2.5 text-xs font-medium text-(--ink-soft) no-underline transition-colors hover:border-(--accent) hover:text-(--accent)"
            >
                {{ tag.name }}
            </Link>
        </li>
    </ul>
</template>
