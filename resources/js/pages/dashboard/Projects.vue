<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ImagePlus, LoaderCircle, Pencil, Plus, Trash2 } from '@lucide/vue';
import { reactive, ref, computed } from 'vue';
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
import type { Project, ProjectScreenshot, Skill } from '@/data/portfolio';
import projectsRoute from '@/routes/dashboard/projects';
import screenshotsRoute from '@/routes/dashboard/projects/screenshots';

/** Dashboard payloads re-add the screenshot `id` (needed for deletion). */
type DashboardProject = Omit<Project, 'screenshots'> & {
    screenshots: (ProjectScreenshot & { id: number })[];
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Projects', href: projectsRoute.index.url() },
        ],
    },
});

const { projects, skills } = defineProps<{
    projects: DashboardProject[];
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
        featured: project.featured ?? false,
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

function onDelete(project: Project) {
    deleteTarget.value = project;
    deleteOpen.value = true;
}

/* Optimistic delete: the row leaves immediately, rolls back on error. */

const deleteTarget = ref<Project | null>(null);
const deleteOpen = ref(false);

function confirmDelete() {
    const project = deleteTarget.value;

    if (!project) {
        return;
    }

    deleteOpen.value = false;
    form.delete(projectsRoute.destroy.url(project.id), {
        preserveScroll: true,
        optimistic: (props) => ({
            projects: (
                (props.projects as DashboardProject[] | undefined) ?? []
            ).filter((p) => p.id !== project.id),
        }),
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
    shotDeleteTarget.value = screenshotId;
    shotDeleteOpen.value = true;
}

const shotDeleteTarget = ref<number | null>(null);
const shotDeleteOpen = ref(false);

function confirmShotDelete() {
    const projectId = shotProjectId.value;
    const screenshotId = shotDeleteTarget.value;

    if (!projectId || screenshotId === null) {
        return;
    }

    shotDeleteOpen.value = false;
    upload.delete(
        screenshotsRoute.destroy.url({
            project: projectId,
            screenshot: screenshotId,
        }),
        {
            preserveScroll: true,
            optimistic: (props) => ({
                projects: (
                    (props.projects as DashboardProject[] | undefined) ?? []
                ).map((project) =>
                    project.id === projectId
                        ? {
                              ...project,
                              screenshots: project.screenshots.filter(
                                  (s) => s.id !== screenshotId,
                              ),
                          }
                        : project,
                ),
            }),
        },
    );
}
</script>

<template>
    <div class="d-dots-bg flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Projects" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Projects"
                description="Manage the projects shown on your public site."
                section-number="03"
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
                                <Label for="project-title" class="d-label"
                                    >Title</Label
                                >
                                <Input
                                    id="project-title"
                                    v-model="form.title"
                                    placeholder="Ledger · accounting"
                                    class="d-sharp"
                                />
                                <InputError :message="form.errors.title" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="project-desc" class="d-label"
                                    >Description</Label
                                >
                                <textarea
                                    id="project-desc"
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Short, punchy summary."
                                    class="d-textarea w-full"
                                />
                                <InputError
                                    :message="form.errors.description"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="project-year" class="d-label"
                                    >Year</Label
                                >
                                <Input
                                    id="project-year"
                                    v-model.number="form.year"
                                    type="number"
                                    class="d-sharp"
                                />
                                <InputError :message="form.errors.year" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="project-published" class="d-label"
                                    >Publish date</Label
                                >
                                <Input
                                    id="project-published"
                                    v-model="form.published_at"
                                    type="date"
                                    aria-describedby="project-published-help"
                                    class="d-sharp"
                                />
                                <p
                                    id="project-published-help"
                                    class="d-ink-soft text-xs"
                                >
                                    Empty = draft. Set to make it public.
                                </p>
                                <InputError
                                    :message="form.errors.published_at"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="featured-toggle" class="d-label"
                                    >Featured</Label
                                >
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
                                <Label for="project-live" class="d-label"
                                    >Live URL</Label
                                >
                                <Input
                                    id="project-live"
                                    v-model="form.live_url"
                                    placeholder="https://…"
                                    type="url"
                                    class="d-sharp"
                                />
                                <InputError :message="form.errors.live_url" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="project-repo" class="d-label"
                                    >Repo URL</Label
                                >
                                <Input
                                    id="project-repo"
                                    v-model="form.repo_url"
                                    placeholder="https://github.com/…"
                                    type="url"
                                    class="d-sharp"
                                />
                                <InputError :message="form.errors.repo_url" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label class="d-label">Skills</Label>
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
                                                ? 'bg-[var(--accent)] text-[var(--paper)]'
                                                : 'bg-[var(--accent-soft)] text-[var(--ink)] hover:bg-[var(--accent-soft)]/70'
                                        "
                                        class="inline-flex items-center px-3 py-1 text-xs font-medium transition-colors focus-visible:ring-2 focus-visible:ring-[var(--accent)] focus-visible:outline-none"
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
                            <Button type="submit" :disabled="form.processing">
                                <LoaderCircle
                                    v-if="form.processing"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                {{ editingId ? 'Save changes' : 'Add project' }}
                            </Button>
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
                            class="group relative overflow-hidden border border-[var(--rule)]"
                        >
                            <img
                                v-if="screenshot.url"
                                :src="screenshot.url"
                                :alt="screenshot.alt ?? ''"
                                loading="lazy"
                                decoding="async"
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
                    <p v-else class="d-ink-soft text-sm">No screenshots yet.</p>

                    <AlertDialog v-model:open="shotDeleteOpen">
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>
                                    Delete screenshot?
                                </AlertDialogTitle>
                                <AlertDialogDescription>
                                    This screenshot will be permanently removed
                                    from the project.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction
                                    variant="destructive"
                                    @click="confirmShotDelete"
                                >
                                    Delete
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>

                    <form class="mt-2 space-y-3" @submit.prevent="uploadShot">
                        <div class="grid gap-2">
                            <Label for="shot-image" class="d-label"
                                >New screenshot</Label
                            >
                            <Input
                                id="shot-image"
                                type="file"
                                accept="image/*"
                                @change="
                                    upload.image =
                                        ($event.target as HTMLInputElement)
                                            .files?.[0] ?? null
                                "
                                class="d-sharp"
                            />
                            <InputError
                                :message="errors.image ?? upload.errors.image"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="shot-alt" class="d-label"
                                >Alt text</Label
                            >
                            <Input
                                id="shot-alt"
                                v-model="upload.alt"
                                placeholder="Describe the screenshot"
                                class="d-sharp"
                            />
                        </div>
                        <Button
                            type="submit"
                            variant="secondary"
                            :disabled="upload.processing"
                            class="w-full"
                        >
                            <LoaderCircle
                                v-if="upload.processing"
                                class="size-4 animate-spin"
                                aria-hidden="true"
                            />
                            <ImagePlus v-else class="size-4" />
                            Upload
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Delete project confirm -->
        <AlertDialog v-model:open="deleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete project?</AlertDialogTitle>
                    <AlertDialogDescription>
                        “{{ deleteTarget?.title }}” and its screenshots will be
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

        <!-- Projects Table -->
        <div class="d-surface">
            <div class="d-rule-b px-4 py-3">
                <h3 class="d-label">{{ projects.length }} projects</h3>
            </div>
            <table class="d-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Skills</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!projects.length">
                        <td colspan="4" class="py-10 text-center">
                            <p class="d-ink-soft text-sm">
                                No projects yet — add your first one.
                            </p>
                        </td>
                    </tr>
                    <tr v-for="project in projects" :key="project.id">
                        <td>
                            <div class="flex items-center gap-2">
                                <p class="d-ink font-medium">
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
                            <p class="d-ink-soft line-clamp-1 text-xs">
                                {{ project.description }}
                            </p>
                        </td>
                        <td>
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
                        <td>
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
                                    >Screenshots of {{ project.title }}</span
                                >
                            </Button>
                        </td>
                        <td>
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
                                    @click="onDelete(project)"
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
        </div>
    </div>
</template>
