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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Skill, SkillCategory } from '@/data/portfolio';
import skillsRoute from '@/routes/dashboard/skills';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Skills', href: skillsRoute.index.url() },
        ],
    },
});

const { skills } = defineProps<{ skills: Skill[] }>();

const categories: SkillCategory[] = [
    'languages',
    'frameworks',
    'databases',
    'devops',
    'platform',
    'security',
];

const form = useForm({
    name: '',
    category: 'languages' as SkillCategory,
});
const editingId = ref<number | null>(null);
const open = ref(false);

function startCreate() {
    editingId.value = null;
    form.reset();
    open.value = true;
}

function startEdit(skill: Skill) {
    editingId.value = skill.id;
    form.defaults({ name: skill.name, category: skill.category });
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
        form.put(skillsRoute.update.url(editingId.value), options);
    } else {
        form.post(skillsRoute.store.url(), options);
    }
}

function onDelete(skill: Skill) {
    deleteTarget.value = skill;
    deleteOpen.value = true;
}

/* Optimistic delete: the row leaves immediately, rolls back on error. */
const deleteTarget = ref<Skill | null>(null);
const deleteOpen = ref(false);

function confirmDelete() {
    const skill = deleteTarget.value;

    if (!skill) {
        return;
    }

    deleteOpen.value = false;
    form.delete(skillsRoute.destroy.url(skill.id), {
        preserveScroll: true,
        optimistic: (props) => ({
            skills: ((props.skills as Skill[] | undefined) ?? []).filter(
                (x) => x.id !== skill.id,
            ),
        }),
    });
}
</script>

<template>
    <div class="d-dots-bg flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Skills" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Skills"
                description="Manage the skills you list in your toolset."
                section-number="05"
            />
            <Dialog v-model:open="open">
                <DialogTrigger as-child>
                    <Button @click="startCreate">
                        <Plus class="size-4" />
                        Add skill
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-md">
                    <form @submit.prevent="save">
                        <DialogHeader>
                            <DialogTitle>{{
                                editingId ? 'Edit skill' : 'Add skill'
                            }}</DialogTitle>
                            <DialogDescription>
                                {{
                                    editingId
                                        ? 'Update this skill.'
                                        : 'Add a technology or tool.'
                                }}
                            </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-4">
                            <div class="grid gap-2">
                                <Label for="skill-name" class="d-label"
                                    >Name</Label
                                >
                                <Input
                                    id="skill-name"
                                    v-model="form.name"
                                    placeholder="Vue"
                                    class="d-sharp"
                                />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label class="d-label">Category</Label>
                                <Select v-model="form.category">
                                    <SelectTrigger class="d-sharp">
                                        <SelectValue placeholder="Category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="cat in categories"
                                            :key="cat"
                                            :value="cat"
                                        >
                                            <span class="capitalize">{{
                                                cat
                                            }}</span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.category" />
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
                                {{ editingId ? 'Save changes' : 'Add skill' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Delete skill confirm -->
        <AlertDialog v-model:open="deleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete skill?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This skill will be permanently removed.
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

        <!-- Skills Table -->
        <div class="d-surface">
            <div class="d-rule-b px-4 py-3">
                <h3 class="d-label">{{ skills.length }} skills</h3>
            </div>
            <table class="d-table">
                <thead>
                    <tr>
                        <th>Skill</th>
                        <th>Category</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!skills.length">
                        <td colspan="3" class="py-10 text-center">
                            <p class="d-ink-soft text-sm">
                                No skills yet — add your first one.
                            </p>
                        </td>
                    </tr>
                    <tr v-for="skill in skills" :key="skill.id">
                        <td class="d-ink font-medium">
                            {{ skill.name }}
                        </td>
                        <td>
                            <Badge
                                variant="outline"
                                class="text-[10px] capitalize"
                            >
                                {{ skill.category }}
                            </Badge>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="startEdit(skill)"
                                >
                                    <Pencil class="size-4" />
                                    <span class="sr-only"
                                        >Edit {{ skill.name }}</span
                                    >
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="hover:bg-destructive/10 hover:text-destructive"
                                    @click="onDelete(skill)"
                                >
                                    <Trash2 class="size-4" />
                                    <span class="sr-only"
                                        >Delete {{ skill.name }}</span
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
