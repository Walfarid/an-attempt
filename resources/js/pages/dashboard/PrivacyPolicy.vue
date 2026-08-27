<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import privacyRoute from '@/routes/dashboard/privacy';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Privacy', href: privacyRoute.edit.url() },
        ],
    },
});

const { policy } = defineProps<{ policy: { id: number; body: string } }>();

const form = useForm({
    body: policy.body ?? '',
});

function save() {
    form.put(privacyRoute.update.url());
}
</script>

<template>
    <div class="d-dots-bg flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Privacy Policy" />

        <Heading
            title="Privacy Policy"
            description="The public privacy disclosure shown on /privacy."
            section-number="09"
        />

        <div class="d-surface max-w-2xl">
            <form class="space-y-6 p-6" @submit.prevent="save">
                <div class="grid gap-2">
                    <Label for="privacy-body" class="d-label">
                        Body (Markdown)
                    </Label>
                    <textarea
                        id="privacy-body"
                        v-model="form.body"
                        rows="24"
                        class="d-textarea w-full"
                    />
                    <InputError :message="form.errors.body" />
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2">
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle
                            v-if="form.processing"
                            class="size-4 animate-spin"
                            aria-hidden="true"
                        />
                        Save policy
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
