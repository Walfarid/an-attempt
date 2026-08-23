<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ImageUp, Pencil, Plus, SquarePen, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    form.reset();
    open.value = true;
}

function startEdit(post: Post) {
    editingId.value = post.id;
    form.defaults({
        title: post.title,
        slug: post.slug,
        excerpt: post.excerpt ?? '',
        body: post.body,
        published_at: post.published_at?.slice(0, 16) ?? '',
    });
    form.reset();
    open.value = true;
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

function onDelete(id: number) {
    form.delete(postsRoute.destroy.url(id), {
        onBefore: () => window.confirm('Delete this post and its cover?'),
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

function removeCover() {
    const postId = coverPostId.value;

    if (!postId) {
        return;
    }

    coverForm.delete(coverRoute.destroy.url({ post: postId }), {
        onBefore: () => window.confirm('Remove this cover image?'),
    });
}

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
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
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
                                <Label for="post-title">Title</Label>
                                <Input
                                    id="post-title"
                                    v-model="form.title"
                                    placeholder="Why I keep coming back to Laravel"
                                />
                                <InputError :message="form.errors.title" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="post-slug">Slug</Label>
                                <Input
                                    id="post-slug"
                                    v-model="form.slug"
                                    placeholder="Auto-generated from title"
                                />
                                <InputError :message="form.errors.slug" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="post-published">Publish at</Label>
                                <Input
                                    id="post-published"
                                    v-model="form.published_at"
                                    type="datetime-local"
                                    aria-describedby="post-published-help"
                                />
                                <p
                                    id="post-published-help"
                                    class="text-xs text-muted-foreground"
                                >
                                    Empty = draft.
                                </p>
                                <InputError
                                    :message="form.errors.published_at"
                                />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="post-excerpt">Excerpt</Label>
                                <Input
                                    id="post-excerpt"
                                    v-model="form.excerpt"
                                    placeholder="Optional teaser; falls back to the body."
                                />
                                <InputError :message="form.errors.excerpt" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="post-body">Body (Markdown)</Label>
                                <textarea
                                    id="post-body"
                                    v-model="form.body"
                                    rows="12"
                                    placeholder="## A heading&#10;&#10;Some *thoughts*…"
                                    class="block w-full rounded-md border border-input bg-transparent px-3 py-2 font-mono text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
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
                            <Button type="submit" :disabled="form.processing">{{
                                editingId ? 'Save changes' : 'Create post'
                            }}</Button>
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
                        class="w-full rounded-md border border-border object-cover"
                    />

                    <form class="space-y-3" @submit.prevent="saveCover">
                        <div class="grid gap-2">
                            <Label for="post-cover">Image</Label>
                            <Input
                                id="post-cover"
                                type="file"
                                accept="image/*"
                                @change="
                                    coverForm.cover =
                                        ($event.target as HTMLInputElement)
                                            .files?.[0] ?? null
                                "
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
                                <ImageUp class="size-4" />
                                Upload / replace
                            </Button>
                            <Button
                                v-if="coverPost?.cover_url"
                                type="button"
                                variant="destructive"
                                :disabled="coverForm.processing"
                                @click="removeCover"
                            >
                                <Trash2 class="size-4" />
                                Remove
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-sm font-medium"
                    >{{ posts.length }} posts</CardTitle
                >
            </CardHeader>
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-border text-left font-mono text-xs tracking-wide text-muted-foreground uppercase"
                        >
                            <th class="px-4 py-2 font-medium">Title</th>
                            <th class="px-4 py-2 font-medium">Status</th>
                            <th class="px-4 py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="post in posts"
                            :key="post.id"
                            class="border-b border-border/60 last:border-0"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ post.title }}</p>
                                <p
                                    class="line-clamp-1 font-mono text-xs text-muted-foreground"
                                >
                                    /posts/{{ post.slug }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        post.published_at
                                            ? 'outline'
                                            : 'secondary'
                                    "
                                    class="text-[10px]"
                                >
                                    {{ publishLabel(post) }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
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
                                        @click="onDelete(post.id)"
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
                                class="px-4 py-8 text-center text-sm text-muted-foreground"
                            >
                                Nothing written yet.
                                <SquarePen class="inline size-4" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
