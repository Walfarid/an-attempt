<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Image, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import type { Media } from '@/data/portfolio';
import mediaRoute from '@/routes/dashboard/media';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Media', href: mediaRoute.index.url() },
        ],
    },
});

const { media } = defineProps<{ media: Media[] }>();

const deleteTarget = ref<Media | null>(null);
const deleteOpen = ref(false);
const deleteForm = useForm({});

function onDelete(m: Media) {
    deleteTarget.value = m;
    deleteOpen.value = true;
}

function confirmDelete() {
    const m = deleteTarget.value;

    if (!m) {
        return;
    }

    deleteOpen.value = false;
    deleteForm.delete(mediaRoute.destroy.url({ medium: m.id }), {
        preserveScroll: true,
        optimistic: (props) => ({
            media: ((props.media as Media[] | undefined) ?? []).filter(
                (x) => x.id !== m.id,
            ),
        }),
    });
}

function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
</script>

<template>
    <div class="d-dots-bg flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Media" />

        <Heading
            title="Media"
            description="Images you can insert inline into any Markdown field."
        />

        <!-- Delete confirm dialog -->
        <AlertDialog v-model:open="deleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete image?</AlertDialogTitle>
                    <AlertDialogDescription>
                        "{{ deleteTarget?.name }}" will be permanently removed.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        variant="destructive"
                        @click="confirmDelete"
                    >
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Media grid -->
        <div
            v-if="media.length"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <div
                v-for="m in media"
                :key="m.id"
                class="d-surface overflow-hidden"
            >
                <div
                    class="aspect-video overflow-hidden border-b border-(--rule)"
                >
                    <img
                        v-if="m.url"
                        :src="m.url"
                        :alt="m.name"
                        loading="lazy"
                        decoding="async"
                        class="h-full w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex h-full items-center justify-center bg-(--accent-soft)"
                    >
                        <Image class="size-8 text-(--ink-soft)" />
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 p-3">
                    <div class="min-w-0 flex-1">
                        <p class="d-ink truncate text-sm font-medium">
                            {{ m.name }}
                        </p>
                        <p class="d-ink-soft text-xs">
                            {{ formatSize(m.size) }}
                        </p>
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="shrink-0 hover:bg-destructive/10 hover:text-destructive"
                        @click="onDelete(m)"
                    >
                        <Trash2 class="size-4" />
                        <span class="sr-only">Delete {{ m.name }}</span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="d-surface">
            <div class="d-ink-soft px-4 py-12 text-center text-sm">
                Nothing uploaded yet.
                <Image class="inline size-4" />
                <p class="mt-1 text-xs">
                    Use the image button in any editor to upload.
                </p>
            </div>
        </div>
    </div>
</template>
