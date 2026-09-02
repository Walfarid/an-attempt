<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from '@lucide/vue';
import { defineAsyncComponent } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Profile as ProfileType } from '@/data/portfolio';
import profileRoute from '@/routes/dashboard/profile';

const MarkdownEditor = defineAsyncComponent(
    () => import('@/components/editor/MarkdownEditor.vue'),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Profile', href: profileRoute.edit.url() },
        ],
    },
});

const { profile } = defineProps<{ profile: ProfileType }>();

const form = useForm({
    name: profile.name,
    headline: profile.headline,
    bio: profile.bio ?? '',
    location: profile.location ?? '',
    github_url: profile.github_url ?? '',
    linkedin_url: profile.linkedin_url ?? '',
});

function save() {
    form.put(profileRoute.update.url());
}
</script>

<template>
    <div class="d-dots-bg flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Profile" />

        <Heading
            title="Profile"
            description="Your public bio shown on the homepage."
        />

        <div class="d-surface max-w-2xl">
            <form class="space-y-6 p-6" @submit.prevent="save">
                <!-- Section: Personal Info -->
                <div class="border-b border-(--rule) pb-6">
                    <h3 class="d-label mb-4">Personal Information</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="profile-name" class="d-label"
                                >Name</Label
                            >
                            <Input
                                id="profile-name"
                                v-model="form.name"
                                required
                                class="d-sharp"
                            />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="profile-location" class="d-label"
                                >Location</Label
                            >
                            <Input
                                id="profile-location"
                                v-model="form.location"
                                placeholder="Singapore · UTC+8"
                                class="d-sharp"
                            />
                            <InputError :message="form.errors.location" />
                        </div>
                    </div>
                </div>

                <!-- Section: Professional -->
                <div class="border-b border-(--rule) pb-6">
                    <h3 class="d-label mb-4">Professional</h3>
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="profile-headline" class="d-label"
                                >Headline</Label
                            >
                            <Input
                                id="profile-headline"
                                v-model="form.headline"
                                required
                                placeholder="Software Engineer"
                                class="d-sharp"
                            />
                            <InputError :message="form.errors.headline" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="profile-bio" class="d-label"
                                >Bio (Markdown)</Label
                            >
                            <MarkdownEditor
                                id="profile-bio"
                                v-model="form.bio"
                                min-height="10rem"
                            />
                            <InputError :message="form.errors.bio" />
                        </div>
                    </div>
                </div>

                <!-- Section: Links -->
                <div class="border-b border-(--rule) pb-6">
                    <h3 class="d-label mb-4">Links</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="profile-github" class="d-label"
                                >GitHub URL</Label
                            >
                            <Input
                                id="profile-github"
                                v-model="form.github_url"
                                type="url"
                                placeholder="https://github.com/…"
                                class="d-sharp"
                            />
                            <InputError :message="form.errors.github_url" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="profile-linkedin" class="d-label"
                                >LinkedIn URL</Label
                            >
                            <Input
                                id="profile-linkedin"
                                v-model="form.linkedin_url"
                                type="url"
                                placeholder="https://linkedin.com/in/…"
                                class="d-sharp"
                            />
                            <InputError :message="form.errors.linkedin_url" />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2">
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle
                            v-if="form.processing"
                            class="size-4 animate-spin"
                            aria-hidden="true"
                        />
                        Save profile
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
