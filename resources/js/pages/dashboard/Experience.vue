<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import type { Experience as ExperienceType } from '@/data/portfolio';
import experienceRoute from '@/routes/dashboard/experience';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Experience', href: experienceRoute.index.url() },
        ],
    },
});

const { experiences } = defineProps<{ experiences: ExperienceType[] }>();

const form = useForm({
    role: '',
    company: '',
    location: '',
    started_at: '2024-01-01',
    ended_at: '',
    summary: '',
    highlights: '' as string,
});
const editingId = ref<number | null>(null);
const open = ref(false);

// The textarea holds one highlight per line; the API expects an array.
form.transform((data) => ({
    ...data,
    highlights: data.highlights
        .split('\n')
        .map((line) => line.replace(/^[•\-*]\s*/, '').trim())
        .filter(Boolean),
}));

function startCreate() {
    editingId.value = null;
    form.reset();
    open.value = true;
}

function startEdit(experience: ExperienceType) {
    editingId.value = experience.id;
    form.defaults({
        role: experience.role,
        company: experience.company,
        location: experience.location ?? '',
        started_at: experience.started_at.slice(0, 10),
        ended_at: experience.ended_at?.slice(0, 10) ?? '',
        summary: experience.summary,
        highlights: experience.highlights.join('\n'),
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
        form.put(experienceRoute.update.url(editingId.value), options);
    } else {
        form.post(experienceRoute.store.url(), options);
    }
}

function onDelete(id: number) {
    form.delete(experienceRoute.destroy.url(id), {
        onBefore: () => window.confirm('Delete this role?'),
    });
}
</script>

<template>
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Experience" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Experience"
                description="Manage the roles on your career timeline."
            />
            <Dialog v-model:open="open">
                <DialogTrigger as-child>
                    <Button @click="startCreate">
                        <Plus class="size-4" />
                        Add role
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-2xl">
                    <form @submit.prevent="save">
                        <DialogHeader>
                            <DialogTitle>{{
                                editingId ? 'Edit role' : 'Add role'
                            }}</DialogTitle>
                            <DialogDescription>
                                {{
                                    editingId
                                        ? 'Update this role.'
                                        : 'Add a role to the timeline.'
                                }}
                            </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="exp-role">Role</Label>
                                <Input
                                    id="exp-role"
                                    v-model="form.role"
                                    placeholder="Senior Developer"
                                />
                                <InputError :message="form.errors.role" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="exp-company">Company</Label>
                                <Input
                                    id="exp-company"
                                    v-model="form.company"
                                    placeholder="Acme Inc."
                                />
                                <InputError :message="form.errors.company" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="exp-location">Location</Label>
                                <Input
                                    id="exp-location"
                                    v-model="form.location"
                                    placeholder="Remote"
                                />
                                <InputError :message="form.errors.location" />
                            </div>
                            <div class="grid gap-2"></div>
                            <div class="grid gap-2">
                                <Label for="exp-started-at">Start date</Label>
                                <Input
                                    id="exp-started-at"
                                    v-model="form.started_at"
                                    type="date"
                                />
                                <InputError :message="form.errors.started_at" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="exp-ended-at">End date</Label>
                                <Input
                                    id="exp-ended-at"
                                    v-model="form.ended_at"
                                    type="date"
                                />
                                <InputError :message="form.errors.ended_at" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="exp-summary">Summary</Label>
                                <textarea
                                    id="exp-summary"
                                    v-model="form.summary"
                                    rows="2"
                                    placeholder="One or two lines about the role."
                                    class="block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                <InputError :message="form.errors.summary" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="exp-highlights"
                                    >Highlights (one per line)</Label
                                >
                                <textarea
                                    id="exp-highlights"
                                    v-model="form.highlights"
                                    rows="4"
                                    placeholder="Shipped a new dashboard&#10;Mentored two juniors"
                                    class="block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                <InputError :message="form.errors.highlights" />
                            </div>
                        </div>
                        <DialogFooter>
                            <DialogClose as-child>
                                <Button type="button" variant="outline"
                                    >Cancel</Button
                                >
                            </DialogClose>
                            <Button type="submit" :disabled="form.processing">{{
                                editingId ? 'Save changes' : 'Add role'
                            }}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <Card>
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-border text-left font-mono text-xs tracking-wide text-muted-foreground uppercase"
                        >
                            <th class="px-4 py-2 font-medium">Role</th>
                            <th class="px-4 py-2 font-medium">Company</th>
                            <th class="px-4 py-2 font-medium">Period</th>
                            <th class="px-4 py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="experience in experiences"
                            :key="experience.id"
                            class="border-b border-border/60 last:border-0"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium">
                                    {{ experience.role }}
                                </p>
                                <p
                                    class="line-clamp-1 text-xs text-muted-foreground"
                                >
                                    {{ experience.summary }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <p>{{ experience.company }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ experience.location }}
                                </p>
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-muted-foreground tabular-nums"
                            >
                                {{
                                    formatDateRange(
                                        experience.started_at,
                                        experience.ended_at,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="startEdit(experience)"
                                    >
                                        <Pencil class="size-4" />
                                        <span class="sr-only"
                                            >Edit {{ experience.role }}</span
                                        >
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="hover:bg-destructive/10 hover:text-destructive"
                                        @click="onDelete(experience.id)"
                                    >
                                        <Trash2 class="size-4" />
                                        <span class="sr-only"
                                            >Delete {{ experience.role }}</span
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
