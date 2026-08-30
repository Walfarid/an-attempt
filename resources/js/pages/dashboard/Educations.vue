<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, LoaderCircle } from '@lucide/vue';
import { ref } from 'vue';
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
import { formatDateRange } from '@/data/portfolio';
import type { Education } from '@/data/portfolio';
import educationsRoute from '@/routes/dashboard/educations';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Education', href: educationsRoute.index.url() },
        ],
    },
});

const { educations } = defineProps<{ educations: Education[] }>();

const form = useForm({
    school: '',
    degree: '',
    started_at: '',
    ended_at: '',
    details: '' as string,
});
const editingId = ref<number | null>(null);
const open = ref(false);

// The textarea holds one detail per line; the API expects an array.
form.transform((data) => ({
    ...data,
    details: data.details
        .split('\n')
        .map((line) => line.replace(/^[•\-*]\s*/, '').trim())
        .filter(Boolean),
}));

function startCreate() {
    editingId.value = null;
    form.reset();
    open.value = true;
}

function startEdit(education: Education) {
    editingId.value = education.id;
    form.defaults({
        school: education.school,
        degree: education.degree,
        started_at: education.started_at?.slice(0, 10) ?? '',
        ended_at: education.ended_at?.slice(0, 10) ?? '',
        details: education.details.join('\n'),
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
        form.put(educationsRoute.update.url(editingId.value), options);
    } else {
        form.post(educationsRoute.store.url(), options);
    }
}

function onDelete(education: Education) {
    deleteTarget.value = education;
    deleteOpen.value = true;
}

/* Optimistic delete: the row leaves immediately, rolls back on error. */
const deleteTarget = ref<Education | null>(null);
const deleteOpen = ref(false);

function confirmDelete() {
    const education = deleteTarget.value;

    if (!education) {
        return;
    }

    deleteOpen.value = false;
    form.delete(educationsRoute.destroy.url(education.id), {
        preserveScroll: true,
        optimistic: (props) => ({
            educations: (
                (props.educations as Education[] | undefined) ?? []
            ).filter((x) => x.id !== education.id),
        }),
    });
}
</script>

<template>
    <div class="d-dots-bg flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Education" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Education"
                description="Academic records shown on your public site."
                section-number="07"
            />
            <Dialog v-model:open="open">
                <DialogTrigger as-child>
                    <Button @click="startCreate">
                        <Plus class="size-4" />
                        Add education
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-xl">
                    <form @submit.prevent="save">
                        <DialogHeader>
                            <DialogTitle>{{
                                editingId ? 'Edit education' : 'Add education'
                            }}</DialogTitle>
                            <DialogDescription>
                                A school and the degree or program.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-4">
                            <div class="grid gap-2">
                                <Label for="edu-school" class="d-label"
                                    >School</Label
                                >
                                <Input
                                    id="edu-school"
                                    v-model="form.school"
                                    placeholder="National University of Singapore"
                                    class="d-sharp"
                                />
                                <InputError :message="form.errors.school" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edu-degree" class="d-label"
                                    >Degree</Label
                                >
                                <Input
                                    id="edu-degree"
                                    v-model="form.degree"
                                    placeholder="M.Tech in Software Engineering"
                                    class="d-sharp"
                                />
                                <InputError :message="form.errors.degree" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="edu-started-at" class="d-label"
                                        >Start date</Label
                                    >
                                    <Input
                                        id="edu-started-at"
                                        v-model="form.started_at"
                                        type="date"
                                        class="d-sharp"
                                    />
                                    <InputError
                                        :message="form.errors.started_at"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="edu-ended-at" class="d-label"
                                        >End date</Label
                                    >
                                    <Input
                                        id="edu-ended-at"
                                        v-model="form.ended_at"
                                        type="date"
                                        class="d-sharp"
                                    />
                                    <p class="d-ink-soft text-xs">
                                        Empty = present.
                                    </p>
                                    <InputError
                                        :message="form.errors.ended_at"
                                    />
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label for="edu-details" class="d-label"
                                    >Details (one per line)</Label
                                >
                                <textarea
                                    id="edu-details"
                                    v-model="form.details"
                                    rows="4"
                                    placeholder="GPA 3.5 / 4.0&#10;Thesis title"
                                    class="d-textarea w-full"
                                />
                                <InputError :message="form.errors.details" />
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
                                    editingId ? 'Save changes' : 'Add education'
                                }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Delete education confirm -->
        <AlertDialog v-model:open="deleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle
                        >Delete education record?</AlertDialogTitle
                    >
                    <AlertDialogDescription>
                        This education record will be permanently removed.
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

        <!-- Education Table -->
        <div class="d-surface">
            <table class="d-table">
                <thead>
                    <tr>
                        <th>School</th>
                        <th>Degree</th>
                        <th>Period</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="education in educations" :key="education.id">
                        <td class="d-ink font-medium">
                            {{ education.school }}
                        </td>
                        <td class="max-w-xs">
                            <p class="line-clamp-1">
                                {{ education.degree }}
                            </p>
                        </td>
                        <td class="d-ink-soft text-xs tabular-nums">
                            {{
                                education.started_at
                                    ? formatDateRange(
                                          education.started_at,
                                          education.ended_at,
                                      )
                                    : '—'
                            }}
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="startEdit(education)"
                                >
                                    <Pencil class="size-4" />
                                    <span class="sr-only"
                                        >Edit {{ education.school }}</span
                                    >
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="hover:bg-destructive/10 hover:text-destructive"
                                    @click="onDelete(education)"
                                >
                                    <Trash2 class="size-4" />
                                    <span class="sr-only"
                                        >Delete {{ education.school }}</span
                                    >
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!educations.length">
                        <td
                            colspan="4"
                            class="d-ink-soft px-4 py-8 text-center text-sm"
                        >
                            No education records yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
