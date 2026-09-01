<script setup lang="ts">
import { LoaderCircle } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Media } from '@/data/portfolio';
import mediaRoute from '@/routes/dashboard/media';

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    insert: [url: string, alt: string];
}>();

const mode = ref<'browse' | 'upload'>('browse');
const media = ref<Media[]>([]);
const loading = ref(false);
const uploading = ref(false);
const error = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

async function loadMedia() {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(mediaRoute.index.url(), {
            headers: { Accept: 'application/json' },
        });
        media.value = await response.json();
    } catch {
        error.value = 'Failed to load images.';
    } finally {
        loading.value = false;
    }
}

watch(open, (isOpen) => {
    if (isOpen) {
        mode.value = 'browse';
        error.value = null;
        loadMedia();
    }
});

function selectImage(m: Media) {
    if (m.url) {
        emit('insert', m.url, m.name);
        open.value = false;
    }
}

async function handleUpload(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    uploading.value = true;
    error.value = null;

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await fetch(mediaRoute.store.url(), {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            const json = (await response.json()) as {
                errors?: { file?: string[] };
            };

            throw new Error(json.errors?.file?.[0] ?? 'Upload failed.');
        }

        const json = (await response.json()) as Media;

        if (json.url) {
            emit('insert', json.url, json.name);
            open.value = false;
        }

        // Reset the file input
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Upload failed.';
    } finally {
        uploading.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Insert image</DialogTitle>
            </DialogHeader>

            <!-- Mode switch -->
            <div class="flex border-b border-[var(--rule)]">
                <button
                    type="button"
                    :class="
                        mode === 'browse'
                            ? 'd-ink border-b-2 border-[var(--ink)]'
                            : 'd-ink-soft'
                    "
                    class="px-4 py-2 text-sm font-medium transition-colors"
                    @click="mode = 'browse'"
                >
                    Browse
                </button>
                <button
                    type="button"
                    :class="
                        mode === 'upload'
                            ? 'd-ink border-b-2 border-[var(--ink)]'
                            : 'd-ink-soft'
                    "
                    class="px-4 py-2 text-sm font-medium transition-colors"
                    @click="mode = 'upload'"
                >
                    Upload
                </button>
            </div>

            <!-- Error message -->
            <p v-if="error" class="text-sm text-destructive">
                {{ error }}
            </p>

            <!-- Browse mode -->
            <div v-if="mode === 'browse'" class="min-h-[200px]">
                <div
                    v-if="loading"
                    class="flex items-center justify-center py-12"
                >
                    <LoaderCircle
                        class="size-6 animate-spin text-muted-foreground"
                    />
                </div>

                <p
                    v-else-if="media.length === 0"
                    class="d-ink-soft py-12 text-center text-sm"
                >
                    No images yet. Upload one to get started.
                </p>

                <div v-else class="grid grid-cols-3 gap-2">
                    <button
                        v-for="m in media"
                        :key="m.id"
                        type="button"
                        class="aspect-square overflow-hidden rounded border border-[var(--rule)] transition-colors hover:border-[var(--ink)]"
                        @click="selectImage(m)"
                    >
                        <img
                            :src="m.url ?? ''"
                            :alt="m.name"
                            loading="lazy"
                            decoding="async"
                            class="size-full object-cover"
                        />
                    </button>
                </div>
            </div>

            <!-- Upload mode -->
            <div v-else class="space-y-4">
                <div class="grid gap-2">
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        :disabled="uploading"
                        class="d-sharp file:d-ink cursor-pointer text-sm file:mr-2 file:rounded file:border file:border-[var(--rule)] file:bg-transparent file:px-2 file:text-sm file:font-medium hover:file:bg-accent"
                        @change="handleUpload"
                    />
                    <p class="d-ink-soft text-xs">
                        JPG, PNG, WebP, GIF, AVIF. Max 4 MB.
                    </p>
                </div>

                <Button v-if="uploading" disabled variant="secondary">
                    <LoaderCircle class="size-4 animate-spin" />
                    Uploading...
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
