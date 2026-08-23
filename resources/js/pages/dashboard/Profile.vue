<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Profile as ProfileType } from '@/data/portfolio';
import profileRoute from '@/routes/dashboard/profile';

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
    bio: profile.bio,
    location: profile.location ?? '',
    github_url: profile.github_url ?? '',
    linkedin_url: profile.linkedin_url ?? '',
});

function save() {
    form.put(profileRoute.update.url());
}
</script>

<template>
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Profile" />

        <Heading
            title="Profile"
            description="Your public bio shown on the homepage."
        />

        <Card class="max-w-2xl">
            <CardContent>
                <form class="grid gap-4" @submit.prevent="save">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="profile-name">Name</Label>
                            <Input
                                id="profile-name"
                                v-model="form.name"
                                required
                            />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="profile-location">Location</Label>
                            <Input
                                id="profile-location"
                                v-model="form.location"
                                placeholder="Singapore · UTC+8"
                            />
                            <InputError :message="form.errors.location" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="profile-headline">Headline</Label>
                        <Input
                            id="profile-headline"
                            v-model="form.headline"
                            required
                            placeholder="Software Engineer"
                        />
                        <InputError :message="form.errors.headline" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="profile-bio">Bio (Markdown)</Label>
                        <textarea
                            id="profile-bio"
                            v-model="form.bio"
                            rows="6"
                            class="block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError :message="form.errors.bio" />
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="profile-github">GitHub URL</Label>
                            <Input
                                id="profile-github"
                                v-model="form.github_url"
                                type="url"
                                placeholder="https://github.com/…"
                            />
                            <InputError :message="form.errors.github_url" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="profile-linkedin">LinkedIn URL</Label>
                            <Input
                                id="profile-linkedin"
                                v-model="form.linkedin_url"
                                type="url"
                                placeholder="https://linkedin.com/in/…"
                            />
                            <InputError :message="form.errors.linkedin_url" />
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Button type="submit" :disabled="form.processing"
                            >Save profile</Button
                        >
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
