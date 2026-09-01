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
import { computed, ref, watch } from 'vue';
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
import type { Post } from '@/data/portfolio';
import postsRoute from '@/routes/dashboard/posts';
import coverRoute from '@/routes/dashboard/posts/cover';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Posts', href: postsRoute.index.url() },
        ],
    },
});

const { posts } = defineProps<{ posts: Post[] }>();

const form = useForm({
    title: '',
    slug: '',
    excerpt: '',
    body: '',
    published_at: '',
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

const coverPostId = ref<number | null>(null);
const coverOpen = ref(false);
const coverPost = computed(
    () => posts.find((p) => p.id === coverPostId.value) ?? null,
);
const coverForm = useForm({
    cover: null as File | null,
});

function startCreate() {
    editingId.value = null;
    slugManuallyEdited.value = false;
    form.reset();
    open.value = true;
}

async function startEdit(post: Post) {
    editingId.value = post.id;
    slugManuallyEdited.value = true;

    // Set initial form data from list view
    form.defaults({
        title: post.title,
        slug: post.slug,
        excerpt: post.excerpt ?? '',
        body: '',
        published_at: post.published_at?.slice(0, 16) ?? '',
    });
    form.reset();
    open.value = true;

    // Lazy-load body from show endpoint
    const response = await fetch(postsRoute.show.url(post.id));
    const fullPost = await response.json();

    form.defaults({
        title: fullPost.title,
        slug: fullPost.slug,
        excerpt: fullPost.excerpt ?? '',
        body: fullPost.body,
        published_at: fullPost.published_at?.slice(0, 16) ?? '',
    });
    form.reset();
}

function save() {
    const options = {
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    };

    if (editingId.value) {
        form.put(postsRoute.update.url(editingId.value), options);
    } else {
        form.post(postsRoute.store.url(), options);
    }
}

function onDelete(post: Post) {
    deleteTarget.value = post;
    deleteOpen.value = true;
}

/* Optimistic delete: the row leaves immediately, rolls back on error. */

const deleteTarget = ref<Post | null>(null);
const deleteOpen = ref(false);

function confirmDelete() {
    const post = deleteTarget.value;

    if (!post) {
        return;
    }

    deleteOpen.value = false;
    form.delete(postsRoute.destroy.url(post.id), {
        preserveScroll: true,
        optimistic: (props) => ({
            posts: ((props.posts as Post[] | undefined) ?? []).filter(
                (p) => p.id !== post.id,
            ),
        }),
    });
}

function openCover(post: Post) {
    coverPostId.value = post.id;
    coverForm.reset();
    coverOpen.value = true;
}

function saveCover() {
    const postId = coverPostId.value;

    if (!postId || !coverForm.cover) {
        return;
    }

    coverForm.put(coverRoute.update.url({ post: postId }), {
        forceFormData: true,
        onSuccess: () => coverForm.reset(),
    });
}

function confirmRemoveCover() {
    const postId = coverPostId.value;

    if (!postId) {
        return;
    }

    coverDeleteOpen.value = false;
    coverForm.delete(coverRoute.destroy.url({ post: postId }), {
        preserveScroll: true,
    });
}

const coverDeleteOpen = ref(false);

function publishLabel(post: Post): string {
    if (post.published_at === null) {
        return 'Draft';
    }

    return new Date(post.published_at) > new Date()
        ? 'Scheduled'
        : new Date(post.published_at).toLocaleDateString('en-US', {
              month: 'short',
              day: 'numeric',
              year: 'numeric',
          });
}
</script>

<template>
    <div class="d-dots-bg flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Posts" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Posts"
                description="Your writing — drafts and published ramblings."
            />
            <Dialog v-model:open="open">
                <DialogTrigger as-child>
                    <Button @click="startCreate">
                        <Plus class="size-4" />
                        New post
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-2xl">
                    <form @submit.prevent="save">
                        <DialogHeader>
                            <DialogTitle>{{
                                editingId ? 'Edit post' : 'New post'
                            }}</DialogTitle>
                            <DialogDescription>
                                Bodies are written in Markdown.
                            </DialogDescription>
                        </DialogHeader>
                        <div
                            class="grid max-h-[60vh] gap-4 overflow-y-auto py-4 sm:grid-cols-2"
                        >
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="post-title" class="d-label"
                                    >Title</Label
                                >
                                <Input
                                    id="post-title"
                                    v-model="form.title"
                                    placeholder="Why I keep coming back to Laravel"
                                    class="d-sharp"
                                />
                                <InputError :message="form.errors.title" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="post-slug" class="d-label"
                                    >Slug</Label
                                >
                                <Input
                                    id="post-slug"
                                    v-model="form.slug"
                                    placeholder="Auto-generated from title"
                                    class="d-sharp"
                                    @input="onSlugInput"
                                />
                                <InputError :message="form.errors.slug" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="post-published" class="d-label"
                                    >Publish at</Label
                                >
                                <Input
                                    id="post-published"
                                    v-model="form.published_at"
                                    type="datetime-local"
                                    aria-describedby="post-published-help"
                                    class="d-sharp"
                                />
                                <p
                                    id="post-published-help"
                                    class="d-ink-soft text-xs"
                                >
                                    Empty = draft.
                                </p>
                                <InputError
                                    :message="form.errors.published_at"
                                />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="post-excerpt" class="d-label"
                                    >Excerpt</Label
                                >
                                <Input
                                    id="post-excerpt"
                                    v-model="form.excerpt"
                                    placeholder="Optional teaser; falls back to the body."
                                    class="d-sharp"
                                />
                                <InputError :message="form.errors.excerpt" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="post-body" class="d-label"
                                    >Body (Markdown)</Label
                                >
                                <textarea
                                    id="post-body"
                                    v-model="form.body"
                                    rows="12"
                                    placeholder="## A heading&#10;&#10;Some *thoughts*…"
                                    class="d-textarea w-full"
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
                                {{ editingId ? 'Save changes' : 'Create post' }}
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
                            {{ coverPost?.title ?? '' }}
                        </DialogDescription>
                    </DialogHeader>

                    <img
                        v-if="coverPost?.cover_url"
                        :src="coverPost.cover_url"
                        alt=""
                        loading="lazy"
                        decoding="async"
                        class="w-full border border-[var(--rule)] object-cover"
                    />

                    <form class="space-y-3" @submit.prevent="saveCover">
                        <div class="grid gap-2">
                            <Label for="post-cover" class="d-label"
                                >Image</Label
                            >
                            <Input
                                id="post-cover"
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
                                :disabled="coverForm.processing"
                                class="flex-1"
                            >
                                <LoaderCircle
                                    v-if="coverForm.processing"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                <ImageUp v-else class="size-4" />
                                Upload / replace
                            </Button>
                            <Button
                                v-if="coverPost?.cover_url"
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
                                                coverPost?.title
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

        <!-- Delete post confirm -->
        <AlertDialog v-model:open="deleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete post?</AlertDialogTitle>
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

        <!-- Posts Table -->
        <div class="d-surface">
            <div class="d-rule-b px-4 py-3">
                <h3 class="d-label">{{ posts.length }} posts</h3>
            </div>
            <table class="d-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="post in posts" :key="post.id">
                        <td>
                            <p class="d-ink font-medium">{{ post.title }}</p>
                            <p class="d-ink-soft line-clamp-1 text-xs">
                                /posts/{{ post.slug }}
                            </p>
                        </td>
                        <td>
                            <Badge
                                :variant="
                                    post.published_at ? 'outline' : 'secondary'
                                "
                                class="text-[10px]"
                            >
                                {{ publishLabel(post) }}
                            </Badge>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="openCover(post)"
                                >
                                    <ImageUp class="size-4" />
                                    <span class="sr-only"
                                        >Cover of {{ post.title }}</span
                                    >
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="startEdit(post)"
                                >
                                    <Pencil class="size-4" />
                                    <span class="sr-only"
                                        >Edit {{ post.title }}</span
                                    >
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="hover:bg-destructive/10 hover:text-destructive"
                                    @click="onDelete(post)"
                                >
                                    <Trash2 class="size-4" />
                                    <span class="sr-only"
                                        >Delete {{ post.title }}</span
                                    >
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!posts.length">
                        <td
                            colspan="3"
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
