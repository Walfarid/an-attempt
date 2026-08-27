<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import type { Publication } from '@/data/portfolio';
import publicationsRoute from '@/routes/dashboard/publications';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Publications', href: publicationsRoute.index.url() },
        ],
    },
});

const { publications } = defineProps<{ publications: Publication[] }>();

const form = useForm({
    citation: '',
    venue: '',
    year: new Date().getFullYear(),
    doi_url: '',
});
const editingId = ref<number | null>(null);
const open = ref(false);

function startCreate() {
    editingId.value = null;
    form.reset();
    open.value = true;
}

function startEdit(publication: Publication) {
    editingId.value = publication.id;
    form.defaults({
        citation: publication.citation,
        venue: publication.venue,
        year: publication.year,
        doi_url: publication.doi_url,
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
        form.put(publicationsRoute.update.url(editingId.value), options);
    } else {
        form.post(publicationsRoute.store.url(), options);
    }
}

function onDelete(id: number) {
    form.delete(publicationsRoute.destroy.url(id), {
        onBefore: () => window.confirm('Delete this publication?'),
    });
}
</script>

<template>
    <div class="d-dots-bg flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Publications" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Publications"
                description="Papers and articles with their DOI links."
                section-number="08"
            />
            <Dialog v-model:open="open">
                <DialogTrigger as-child>
                    <Button @click="startCreate">
                        <Plus class="size-4" />
                        Add publication
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-xl">
                    <form @submit.prevent="save">
                        <DialogHeader>
                            <DialogTitle>{{
                                editingId
                                    ? 'Edit publication'
                                    : 'Add publication'
                            }}</DialogTitle>
                            <DialogDescription>
                                The citation as it should appear publicly.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-4">
                            <div class="grid gap-2">
                                <Label for="pub-citation" class="d-label"
                                    >Citation</Label
                                >
                                <textarea
                                    id="pub-citation"
                                    v-model="form.citation"
                                    rows="3"
                                    placeholder='Waworundeng, J. "AirQMon…"'
                                    class="d-textarea w-full"
                                />
                                <InputError :message="form.errors.citation" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="pub-venue" class="d-label"
                                    >Venue</Label
                                >
                                <Input
                                    id="pub-venue"
                                    v-model="form.venue"
                                    placeholder="Cogito Smart Journal, 6(2)"
                                    class="d-sharp"
                                />
                                <InputError :message="form.errors.venue" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="pub-year" class="d-label"
                                        >Year</Label
                                    >
                                    <Input
                                        id="pub-year"
                                        v-model.number="form.year"
                                        type="number"
                                        class="d-sharp"
                                    />
                                    <InputError :message="form.errors.year" />
                                </div>
                                <div class="grid gap-2"></div>
                            </div>
                            <div class="grid gap-2">
                                <Label for="pub-doi" class="d-label"
                                    >DOI URL</Label
                                >
                                <Input
                                    id="pub-doi"
                                    v-model="form.doi_url"
                                    type="url"
                                    placeholder="https://doi.org/10.…"
                                    class="d-sharp"
                                />
                                <InputError :message="form.errors.doi_url" />
                            </div>
                        </div>
                        <DialogFooter>
                            <DialogClose as-child>
                                <Button type="button" variant="outline"
                                    >Cancel</Button
                                >
                            </DialogClose>
                            <Button type="submit" :disabled="form.processing">{{
                                editingId ? 'Save changes' : 'Add publication'
                            }}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Publications Table -->
        <div class="d-surface">
            <table class="d-table">
                <thead>
                    <tr>
                        <th>Citation</th>
                        <th>Venue</th>
                        <th class="text-right">Year</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="publication in publications"
                        :key="publication.id"
                    >
                        <td class="max-w-sm">
                            <p class="d-ink line-clamp-2 font-medium">
                                {{ publication.citation }}
                            </p>
                            <a
                                :href="publication.doi_url"
                                target="_blank"
                                rel="noreferrer noopener"
                                class="d-ink-soft line-clamp-1 text-xs underline-offset-2 hover:text-[var(--accent)] hover:underline"
                                >{{ publication.doi_url }}</a
                            >
                        </td>
                        <td class="d-ink-soft">
                            {{ publication.venue }}
                        </td>
                        <td class="text-right tabular-nums">
                            {{ publication.year }}
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="startEdit(publication)"
                                >
                                    <Pencil class="size-4" />
                                    <span class="sr-only"
                                        >Edit {{ publication.venue }}</span
                                    >
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="hover:bg-destructive/10 hover:text-destructive"
                                    @click="onDelete(publication.id)"
                                >
                                    <Trash2 class="size-4" />
                                    <span class="sr-only"
                                        >Delete {{ publication.venue }}</span
                                    >
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!publications.length">
                        <td
                            colspan="4"
                            class="d-ink-soft px-4 py-8 text-center text-sm"
                        >
                            No publications yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
