<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';

type Props = {
    status?: string;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6 p-4 sm:p-6">
        <Heading
            variant="small"
            title="Profile"
            description="Update your name and email address"
            section-number="01"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-0"
            v-slot="{ errors, processing }"
        >
            <div class="d-surface divide-y divide-(--rule)">
                <div class="grid gap-0 p-4">
                    <div class="flex items-start justify-between">
                        <Label for="name" class="d-label pt-2"> Name </Label>
                        <div class="w-2/3">
                            <Input
                                id="name"
                                class="rounded-none border-0 border-b border-(--rule) bg-transparent px-0 font-mono text-sm focus:border-(--accent) focus:ring-0"
                                name="name"
                                :default-value="user.name"
                                required
                                autocomplete="name"
                                placeholder="Full name"
                            />
                            <InputError class="mt-1" :message="errors.name" />
                        </div>
                    </div>
                </div>

                <div class="grid gap-0 p-4">
                    <div class="flex items-start justify-between">
                        <Label for="email" class="d-label pt-2">
                            Email address
                        </Label>
                        <div class="w-2/3">
                            <Input
                                id="email"
                                type="email"
                                class="rounded-none border-0 border-b border-(--rule) bg-transparent px-0 font-mono text-sm focus:border-(--accent) focus:ring-0 disabled:opacity-50"
                                name="email"
                                :default-value="user.email"
                                required
                                autocomplete="username"
                                placeholder="Email address"
                                disabled
                            />
                            <InputError class="mt-1" :message="errors.email" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end p-4">
                    <Button
                        :disabled="processing"
                        data-test="update-profile-button"
                    >
                        Save
                    </Button>
                </div>
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
