<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ImagePlus, Pencil, Plus, Trash2 } from '@lucide/vue';
import { reactive, ref, computed } from 'vue';
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
import type { Project, Skill } from '@/data/portfolio';
import projectsRoute from '@/routes/dashboard/projects';
import screenshotsRoute from '@/routes/dashboard/projects/screenshots';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Projects', href: projectsRoute.index.url() },
        ],
    },
});

const { projects, skills } = defineProps<{
    projects: Project[];
    skills: Skill[];
}>();

const form = useForm({
    title: '',
    description: '',
    year: new Date().getFullYear(),
    live_url: '',
    repo_url: '',
    featured: false,
    published_at: '',
    skills: [] as number[],
});

const editingId = ref<number | null>(null);
const open = ref(false);

const shotProjectId = ref<number | null>(null);
const shotsOpen = ref(false);
// Computed so the dialog reflects fresh props after each upload/delete.
const shotProject = computed(
    () => projects.find((p) => p.id === shotProjectId.value) ?? null,
);
const upload = useForm({
    alt: '',
    image: null as File | null,
});

const errors = reactive<Record<string, string | undefined>>({});

function startCreate() {
    editingId.value = null;
    form.reset();
    open.value = true;
}

function startEdit(project: Project) {
    editingId.value = project.id;
    form.defaults({
        title: project.title,
        description: project.description,
        year: project.year,
        live_url: project.live_url ?? '',
        repo_url: project.repo_url ?? '',
        featured: project.featured,
        published_at: project.published_at?.slice(0, 10) ?? '',
        skills: project.skills.map((s) => s.id),
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
        form.put(projectsRoute.update.url(editingId.value), options);
    } else {
        form.post(projectsRoute.store.url(), options);
    }
}

function onDelete(id: number) {
    form.delete(projectsRoute.destroy.url(id), {
        onBefore: () =>
            window.confirm('Delete this project and its screenshots?'),
    });
}

function toggleSkill(id: number) {
    const i = form.skills.indexOf(id);

    if (i === -1) {
        form.skills.push(id);
    } else {
        form.skills.splice(i, 1);
    }
}

function openShots(project: Project) {
    shotProjectId.value = project.id;
    upload.reset();
    shotsOpen.value = true;
}

function uploadShot() {
    const projectId = shotProjectId.value;

    if (!projectId) {
        return;
    }

    if (!upload.image) {
        errors.image = 'Choose an image to upload';

        return;
    }

    delete errors.image;
    upload.post(screenshotsRoute.store.url({ project: projectId }), {
        forceFormData: true,
        onSuccess: () => upload.reset(),
    });
}

function removeShot(screenshotId: number) {
    const projectId = shotProjectId.value;

    if (!projectId) {
        return;
    }

    upload.delete(
        screenshotsRoute.destroy.url({
            project: projectId,
            screenshot: screenshotId,
        }),
        {
            onBefore: () => window.confirm('Delete this screenshot?'),
        },
    );
}
</script>

<template>
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Projects" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Projects"
                description="Manage the projects shown on your public site."
            />
            <Dialog v-model:open="open">
                <DialogTrigger as-child>
                    <Button @click="startCreate">
                        <Plus class="size-4" />
                        Add project
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-2xl">
                    <form @submit.prevent="save">
                        <DialogHeader>
                            <DialogTitle>{{
                                editingId ? 'Edit project' : 'Add project'
                            }}</DialogTitle>
                            <DialogDescription>
                                {{
                                    editingId
                                        ? 'Update this project entry.'
                                        : 'Create a new project entry.'
                                }}
                            </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-4 sm:grid-cols-2">
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="project-title">Title</Label>
                                <Input
                                    id="project-title"
                                    v-model="form.title"
                                    placeholder="Ledger · accounting"
                                />
                                <InputError :message="form.errors.title" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="project-desc">Description</Label>
                                <textarea
                                    id="project-desc"
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Short, punchy summary."
                                    class="block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                <InputError
                                    :message="form.errors.description"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="project-year">Year</Label>
                                <Input
                                    id="project-year"
                                    v-model.number="form.year"
                                    type="number"
                                />
                                <InputError :message="form.errors.year" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="project-published"
                                    >Publish date</Label
                                >
                                <Input
                                    id="project-published"
                                    v-model="form.published_at"
                                    type="date"
                                    aria-describedby="project-published-help"
                                />
                                <p
                                    id="project-published-help"
                                    class="text-xs text-muted-foreground"
                                >
                                    Empty = draft. Set to make it public.
                                </p>
                                <InputError
                                    :message="form.errors.published_at"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="featured-toggle">Featured</Label>
                                <div class="flex h-9 items-center gap-2">
                                    <input
                                        id="featured-toggle"
                                        v-model="form.featured"
                                        type="checkbox"
                                        class="size-4"
                                    />
                                    <Label
                                        for="featured-toggle"
                                        class="font-normal"
                                        >Show on homepage</Label
                                    >
                                </div>
                            </div>
                            <div class="grid gap-2"></div>
                            <div class="grid gap-2">
                                <Label for="project-live">Live URL</Label>
                                <Input
                                    id="project-live"
                                    v-model="form.live_url"
                                    placeholder="https://…"
                                    type="url"
                                />
                                <InputError :message="form.errors.live_url" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="project-repo">Repo URL</Label>
                                <Input
                                    id="project-repo"
                                    v-model="form.repo_url"
                                    placeholder="https://github.com/…"
                                    type="url"
                                />
                                <InputError :message="form.errors.repo_url" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label>Skills</Label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="skill in skills"
                                        :key="skill.id"
                                        type="button"
                                        :aria-pressed="
                                            form.skills.includes(skill.id)
                                        "
                                        :class="
                                            form.skills.includes(skill.id)
                                                ? 'bg-accent-primary text-primary-foreground'
                                                : 'bg-secondary text-secondary-foreground hover:bg-secondary/70'
                                        "
                                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                        @click="toggleSkill(skill.id)"
                                    >
                                        {{ skill.name }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <DialogClose as-child>
                                <Button type="button" variant="outline"
                                    >Cancel</Button
                                >
                            </DialogClose>
                            <Button type="submit" :disabled="form.processing">{{
                                editingId ? 'Save changes' : 'Add project'
                            }}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Screenshots dialog -->
            <Dialog v-model:open="shotsOpen">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Screenshots</DialogTitle>
                        <DialogDescription>
                            {{
                                shotProject?.title ??
                                'Manage project screenshots.'
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <ul
                        v-if="shotProject && shotProject.screenshots.length"
                        class="grid grid-cols-3 gap-3"
                    >
                        <li
                            v-for="screenshot in shotProject.screenshots"
                            :key="screenshot.id"
                            class="group relative overflow-hidden rounded-md border border-border"
                        >
                            <img
                                v-if="screenshot.url"
                                :src="screenshot.url"
                                :alt="screenshot.alt ?? ''"
                                class="aspect-video w-full object-cover"
                            />
                            <Button
                                variant="destructive"
                                size="icon-sm"
                                class="absolute top-1 right-1 opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100"
                                @click="removeShot(screenshot.id)"
                            >
                                <Trash2 class="size-3.5" />
                                <span class="sr-only">Delete screenshot</span>
                            </Button>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        No screenshots yet.
                    </p>

                    <form class="mt-2 space-y-3" @submit.prevent="uploadShot">
                        <div class="grid gap-2">
                            <Label for="shot-image">New screenshot</Label>
                            <Input
                                id="shot-image"
                                type="file"
                                accept="image/*"
                                @change="
                                    upload.image =
                                        ($event.target as HTMLInputElement)
                                            .files?.[0] ?? null
                                "
                            />
                            <InputError
                                :message="errors.image ?? upload.errors.image"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="shot-alt">Alt text</Label>
                            <Input
                                id="shot-alt"
                                v-model="upload.alt"
                                placeholder="Describe the screenshot"
                            />
                        </div>
                        <Button
                            type="submit"
                            variant="secondary"
                            :disabled="upload.processing"
                            class="w-full"
                        >
                            <ImagePlus class="size-4" />
                            Upload
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-sm font-medium">
                    {{ projects.length }} projects
                </CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-border text-left font-mono text-xs tracking-wide text-muted-foreground uppercase"
                        >
                            <th class="px-4 py-2 font-medium">Project</th>
                            <th class="px-4 py-2 font-medium">Skills</th>
                            <th class="px-4 py-2 font-medium">Status</th>
                            <th class="px-4 py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="project in projects"
                            :key="project.id"
                            class="border-b border-border/60 last:border-0"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium">
                                        {{ project.title }}
                                    </p>
                                    <Badge
                                        v-if="project.featured"
                                        variant="secondary"
                                        class="text-[10px]"
                                    >
                                        Featured
                                    </Badge>
                                </div>
                                <p
                                    class="line-clamp-1 text-xs text-muted-foreground"
                                >
                                    {{ project.description }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="skill in project.skills"
                                        :key="skill.id"
                                        variant="outline"
                                        class="text-[10px]"
                                    >
                                        {{ skill.name }}
                                    </Badge>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    v-if="project.published_at"
                                    variant="outline"
                                    class="text-[10px]"
                                >
                                    Published
                                </Badge>
                                <Badge
                                    v-else
                                    variant="secondary"
                                    class="text-[10px]"
                                >
                                    Draft
                                </Badge>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="ml-1"
                                    @click="openShots(project)"
                                >
                                    <ImagePlus class="size-4" />
                                    <span class="sr-only"
                                        >Screenshots of
                                        {{ project.title }}</span
                                    >
                                </Button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="startEdit(project)"
                                    >
                                        <Pencil class="size-4" />
                                        <span class="sr-only"
                                            >Edit {{ project.title }}</span
                                        >
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="hover:bg-destructive/10 hover:text-destructive"
                                        @click="onDelete(project.id)"
                                    >
                                        <Trash2 class="size-4" />
                                        <span class="sr-only"
                                            >Delete {{ project.title }}</span
                                        >
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
