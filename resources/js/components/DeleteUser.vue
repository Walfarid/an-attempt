<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
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
</script>

<template>
    <div class="d-rule space-y-6 pt-6">
        <Heading
            variant="small"
            title="Delete account"
            description="Delete your account and all of its resources"
            section-number="02"
        />
        <div class="d-surface">
            <div class="border-b border-destructive/30 p-4">
                <div class="space-y-1 font-mono text-destructive">
                    <p class="text-xs font-semibold tracking-wider uppercase">
                        ⚠ Warning
                    </p>
                    <p class="text-xs text-destructive/80">
                        Please proceed with caution, this cannot be undone.
                    </p>
                </div>
            </div>
            <div class="p-4">
                <Dialog>
                    <DialogTrigger as-child>
                        <Button
                            variant="destructive"
                            data-test="delete-user-button"
                            class="font-mono text-xs tracking-wider uppercase"
                        >
                            Delete account
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="rounded-none">
                        <Form
                            v-bind="ProfileController.destroy.form()"
                            reset-on-success
                            :options="{
                                preserveScroll: true,
                            }"
                            class="space-y-6"
                            v-slot="{ processing, reset, clearErrors }"
                        >
                            <DialogHeader class="space-y-3">
                                <DialogTitle
                                    class="font-mono text-sm tracking-wider uppercase"
                                >
                                    Are you sure you want to delete your
                                    account?
                                </DialogTitle>
                                <DialogDescription
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    Once your account is deleted, all of its
                                    resources and data will also be permanently
                                    deleted. Please confirm you would like to
                                    permanently delete your account.
                                </DialogDescription>
                            </DialogHeader>

                            <DialogFooter class="gap-2">
                                <DialogClose as-child>
                                    <Button
                                        variant="secondary"
                                        class="font-mono text-xs tracking-wider uppercase"
                                        @click="
                                            () => {
                                                clearErrors();
                                                reset();
                                            }
                                        "
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    variant="destructive"
                                    :disabled="processing"
                                    data-test="confirm-delete-user-button"
                                    class="font-mono text-xs tracking-wider uppercase"
                                >
                                    Delete account
                                </Button>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </div>
</template>
