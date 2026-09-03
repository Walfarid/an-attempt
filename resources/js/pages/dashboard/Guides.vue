<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    ImageUp,
    LoaderCircle,
    Pencil,
    Plus,
    SquarePen,
    Trash2,
} from '@lucide/vue';
import { computed, defineAsyncComponent, ref, watch } from 'vue';
import PostPicker from '@/components/editor/PostPicker.vue';

const MarkdownEditor = defineAsyncComponent(
    () => import('@/components/editor/MarkdownEditor.vue'),
);
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { processImage } from '@/composables/useImageProcessor';
import type { GuideListItem, PostOption } from '@/data/portfolio';
import { formatDate } from '@/lib/utils';
import guidesRoute from '@/routes/dashboard/guides';
import coverRoute from '@/routes/dashboard/guides/cover';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Guides', href: guidesRoute.index.url() },
        ],
    },
});

const { guides, postTitles } = defineProps<{
    guides: GuideListItem[];
    postTitles: PostOption[];
}>();

const form = useForm({
    title: '',
    slug: '',
    teaser: '',
    prerequisites: '',
    estimated_time: '',
    body: '',
    published_at: '',
    posts: [] as number[],
});

const editingId = ref<number | null>(null);
const open = ref(false);
const slugManuallyEdited = ref(false);

/* Slug auto-generation from title */
watch(
    () => form.title,
    (title) => {
        if (editingId.value || slugManuallyEdited.value) {
            return;
        }

        if (!title) {
            form.slug = '';

            return;
        }

        form.slug = title
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    },
);

function onSlugInput() {
    slugManuallyEdited.value = true;
}

const coverGuideId = ref<number | null>(null);
const coverOpen = ref(false);
const coverGuide = computed(
    () => guides.find((g) => g.id === coverGuideId.value) ?? null,
);
const coverForm = useForm({
    cover: null as File | null,
});
const coverProcessing = ref(false);

function startCreate() {
    editingId.value = null;
    slugManuallyEdited.value = false;
    form.reset();
    open.value = true;
}

async function startEdit(guide: GuideListItem) {
    editingId.value = guide.id;
    slugManuallyEdited.value = true;

    // Set initial form data from list view
    form.defaults({
        title: guide.title,
        slug: guide.slug,
        teaser: '',
        prerequisites: '',
        estimated_time: guide.estimated_time ?? '',
        body: '',
        published_at: guide.published_at?.slice(0, 16) ?? '',
        posts: [],
    });
    form.reset();
    open.value = true;

    // Lazy-load body + related posts from show endpoint
    try {
        const response = await fetch(guidesRoute.show.url(guide.id));

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const fullGuide = await response.json();

        form.defaults({
            title: fullGuide.title,
            slug: fullGuide.slug,
            teaser: fullGuide.teaser ?? '',
            prerequisites: fullGuide.prerequisites ?? '',
            estimated_time: fullGuide.estimated_time ?? '',
            body: fullGuide.body ?? '',
            published_at: fullGuide.published_at?.slice(0, 16) ?? '',
            posts: fullGuide.posts ?? [],
        });
        form.reset();
    } catch {
        open.value = false;
        form.reset();
        void import('vue-sonner').then(({ toast }) => {
            toast.error('Could not load the full guide. Please try again.');
        });
    }
}

function save() {
    const options = {
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    };

    if (editingId.value) {
        form.put(guidesRoute.update.url(editingId.value), options);
    } else {
        form.post(guidesRoute.store.url(), options);
    }
}

function onDelete(guide: GuideListItem) {
    deleteTarget.value = guide;
    deleteOpen.value = true;
}

/* Optimistic delete: the row leaves immediately, rolls back on error. */

const deleteTarget = ref<GuideListItem | null>(null);
const deleteOpen = ref(false);

function confirmDelete() {
    const guide = deleteTarget.value;

    if (!guide) {
        return;
    }

    deleteOpen.value = false;
    form.delete(guidesRoute.destroy.url(guide.id), {
        preserveScroll: true,
        optimistic: (props) => ({
            guides: (
                (props.guides as GuideListItem[] | undefined) ?? []
            ).filter((g) => g.id !== guide.id),
        }),
    });
}

function openCover(guide: GuideListItem) {
    coverGuideId.value = guide.id;
    coverForm.reset();
    coverOpen.value = true;
}

async function saveCover() {
    const guideId = coverGuideId.value;

    if (!guideId || !coverForm.cover) {
        return;
    }

    coverProcessing.value = true;

    try {
        coverForm.cover = await processImage(coverForm.cover);
    } catch {
        coverProcessing.value = false;

        return;
    }

    coverProcessing.value = false;

    coverForm.put(coverRoute.update.url({ guide: guideId }), {
        forceFormData: true,
        onSuccess: () => coverForm.reset(),
    });
}

function confirmRemoveCover() {
    const guideId = coverGuideId.value;

    if (!guideId) {
        return;
    }

    coverDeleteOpen.value = false;
    coverForm.delete(coverRoute.destroy.url({ guide: guideId }), {
        preserveScroll: true,
    });
}

const coverDeleteOpen = ref(false);

function publishLabel(guide: GuideListItem): string {
    if (guide.published_at === null) {
        return 'Draft';
    }

    return new Date(guide.published_at) > new Date()
        ? 'Scheduled'
        : formatDate(guide.published_at);
}
</script>

<template>
    <div class="d-dots-bg flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Guides" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Guides"
                description="Step-by-step walkthroughs that reference your posts."
            />
            <Dialog v-model:open="open">
                <DialogTrigger as-child>
                    <Button @click="startCreate">
                        <Plus class="size-4" />
                        New guide
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-2xl">
                    <form @submit.prevent="save">
                        <DialogHeader>
                            <DialogTitle>{{
                                editingId ? 'Edit guide' : 'New guide'
                            }}</DialogTitle>
                            <DialogDescription>
                                Bodies are written in Markdown.
                            </DialogDescription>
                        </DialogHeader>
                        <div
                            class="grid max-h-[60vh] gap-4 overflow-y-auto py-4 sm:grid-cols-2"
                        >
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="guide-title" class="d-label"
                                    >Title</Label
                                >
                                <Input
                                    id="guide-title"
                                    v-model="form.title"
                                    placeholder="Getting started with Laravel"
                                    class="d-sharp"
                                />
                                <InputError :message="form.errors.title" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="guide-slug" class="d-label"
                                    >Slug</Label
                                >
                                <Input
                                    id="guide-slug"
                                    v-model="form.slug"
                                    placeholder="Auto-generated from title"
                                    class="d-sharp"
                                    @input="onSlugInput"
                                />
                                <InputError :message="form.errors.slug" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="guide-published" class="d-label"
                                    >Publish at</Label
                                >
                                <Input
                                    id="guide-published"
                                    v-model="form.published_at"
                                    type="datetime-local"
                                    aria-describedby="guide-published-help"
                                    class="d-sharp"
                                />
                                <p
                                    id="guide-published-help"
                                    class="d-ink-soft text-xs"
                                >
                                    Empty = draft.
                                </p>
                                <InputError
                                    :message="form.errors.published_at"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="guide-course" class="d-label"
                                    >Estimated time</Label
                                >
                                <Input
                                    id="guide-course"
                                    v-model="form.estimated_time"
                                    placeholder="30 minutes"
                                    class="d-sharp"
                                />
                                <InputError
                                    :message="form.errors.estimated_time"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="guide-teaser" class="d-label"
                                    >Teaser</Label
                                >
                                <Input
                                    id="guide-teaser"
                                    v-model="form.teaser"
                                    placeholder="One-line summary for cards."
                                    class="d-sharp"
                                />
                                <InputError :message="form.errors.teaser" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="guide-prereqs" class="d-label"
                                    >Prerequisites</Label
                                >
                                <textarea
                                    id="guide-prereqs"
                                    v-model="form.prerequisites"
                                    rows="3"
                                    placeholder="PHP, Composer, a Laravel project…"
                                    class="d-textarea"
                                />
                                <InputError
                                    :message="form.errors.prerequisites"
                                />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="guide-posts" class="d-label"
                                    >Related posts</Label
                                >
                                <PostPicker
                                    id="guide-post-picker"
                                    v-model="form.posts"
                                    :options="postTitles"
                                    class="d-sharp"
                                />
                                <p class="d-ink-soft text-xs">
                                    Posts this guide references; shown under the
                                    guide.
                                </p>
                                <InputError :message="form.errors.posts" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="guide-body" class="d-label"
                                    >Body (Markdown)</Label
                                >
                                <MarkdownEditor
                                    id="guide-body"
                                    v-model="form.body"
                                    placeholder="## Step 1

Set things up…"
                                    min-height="18rem"
                                />
                                <InputError :message="form.errors.body" />
                            </div>
                        </div>
                        <DialogFooter>
                            <DialogClose as-child>
                                <Button type="button" variant="outline"
                                    >Cancel</Button
                                >
                            </DialogClose>
                            <Button type="submit" :disabled="form.processing">
                                <LoaderCircle
                                    v-if="form.processing"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                {{
                                    editingId ? 'Save changes' : 'Create guide'
                                }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Cover dialog -->
            <Dialog v-model:open="coverOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Cover image</DialogTitle>
                        <DialogDescription>
                            {{ coverGuide?.title ?? '' }}
                        </DialogDescription>
                    </DialogHeader>

                    <img
                        v-if="coverGuide?.cover_url"
                        :src="coverGuide.cover_url"
                        alt=""
                        loading="lazy"
                        decoding="async"
                        class="w-full border border-[var(--rule)] object-cover"
                    />

                    <form class="space-y-3" @submit.prevent="saveCover">
                        <div class="grid gap-2">
                            <Label for="guide-cover" class="d-label"
                                >Image</Label
                            >
                            <Input
                                id="guide-cover"
                                type="file"
                                accept="image/*"
                                @change="
                                    coverForm.cover =
                                        ($event.target as HTMLInputElement)
                                            .files?.[0] ?? null
                                "
                                class="d-sharp"
                            />
                            <InputError :message="coverForm.errors.cover" />
                        </div>
                        <div class="flex gap-2">
                            <Button
                                type="submit"
                                variant="secondary"
                                :disabled="
                                    coverForm.processing || coverProcessing
                                "
                                class="flex-1"
                            >
                                <LoaderCircle
                                    v-if="
                                        coverForm.processing || coverProcessing
                                    "
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                <ImageUp v-else class="size-4" />
                                {{
                                    coverProcessing
                                        ? 'Processing...'
                                        : 'Upload / replace'
                                }}
                            </Button>
                            <Button
                                v-if="coverGuide?.cover_url"
                                type="button"
                                variant="destructive"
                                :disabled="coverForm.processing"
                                @click="coverDeleteOpen = true"
                            >
                                <Trash2 class="size-4" />
                                Remove
                            </Button>

                            <AlertDialog v-model:open="coverDeleteOpen">
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>
                                            Remove cover image?
                                        </AlertDialogTitle>
                                        <AlertDialogDescription>
                                            The cover of “{{
                                                coverGuide?.title
                                            }}” will be permanently removed.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel
                                            >Cancel</AlertDialogCancel
                                        >
                                        <AlertDialogAction
                                            variant="destructive"
                                            @click="confirmRemoveCover"
                                        >
                                            Remove
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Delete guide confirm -->
        <AlertDialog v-model:open="deleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete guide?</AlertDialogTitle>
                    <AlertDialogDescription>
                        “{{ deleteTarget?.title }}” and its cover image will be
                        permanently removed.
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

        <!-- Guides Table -->
        <div class="d-surface">
            <div class="d-rule-b px-4 py-3">
                <h3 class="d-label">{{ guides.length }} guides</h3>
            </div>
            <table class="d-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="guide in guides" :key="guide.id">
                        <td>
                            <div class="flex items-center gap-3">
                                <img
                                    v-if="guide.cover_url"
                                    :src="guide.cover_url"
                                    :alt="`Cover of ${guide.title}`"
                                    loading="lazy"
                                    decoding="async"
                                    class="size-10 shrink-0 rounded border border-[var(--rule)] object-cover"
                                />
                                <div>
                                    <p class="d-ink font-medium">
                                        {{ guide.title }}
                                    </p>
                                    <p class="d-ink-soft line-clamp-1 text-xs">
                                        /guides/{{ guide.slug }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <Badge
                                :variant="
                                    guide.published_at ? 'outline' : 'secondary'
                                "
                                class="text-[10px]"
                            >
                                {{ publishLabel(guide) }}
                            </Badge>
                        </td>
                        <td>
                            <span
                                v-if="guide.estimated_time"
                                class="d-ink-soft text-xs"
                            >
                                {{ guide.estimated_time }}
                            </span>
                            <span v-else class="text-xs text-transparent"
                                >—</span
                            >
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="openCover(guide)"
                                >
                                    <ImageUp class="size-4" />
                                    <span class="sr-only"
                                        >Cover of {{ guide.title }}</span
                                    >
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="startEdit(guide)"
                                >
                                    <Pencil class="size-4" />
                                    <span class="sr-only"
                                        >Edit {{ guide.title }}</span
                                    >
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="hover:bg-destructive/10 hover:text-destructive"
                                    @click="onDelete(guide)"
                                >
                                    <Trash2 class="size-4" />
                                    <span class="sr-only"
                                        >Delete {{ guide.title }}</span
                                    >
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!guides.length">
                        <td
                            colspan="4"
                            class="d-ink-soft px-4 py-8 text-center text-sm"
                        >
                            Nothing written yet.
                            <SquarePen class="inline size-4" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
