<script setup lang="ts">
/**
 * HomeSkeleton — placeholder for the deferred below-the-fold sections.
 *
 * Mimics the shape of the real card layouts so the page height feels
 * stable while the sections stream in after the hero paints. Everything
 * is `aria-hidden`; the pulsing `.d-skeleton` blocks disappear as soon
 * as the deferred props resolve (and the global reduced-motion rule
 * freezes the pulse for users who opted out).
 */
</script>

<template>
    <div aria-hidden="true">
        <!-- Skills -->
        <section class="d-rule">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
                <div class="mb-10 max-w-2xl">
                    <div class="d-skeleton h-3 w-16" />
                    <div class="d-skeleton mt-3 h-8 w-64 max-w-full" />
                    <div class="d-skeleton mt-3 h-4 w-96 max-w-full" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="i in 6"
                        :key="i"
                        class="d-surface flex items-center gap-4 p-5"
                    >
                        <div class="d-skeleton size-10 shrink-0" />
                        <div class="flex-1">
                            <div class="d-skeleton h-4 w-24" />
                            <div class="d-skeleton mt-2 h-3 w-12" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Experience -->
        <section class="d-rule">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
                <div class="mb-10">
                    <div class="d-skeleton h-3 w-20" />
                    <div class="d-skeleton mt-3 h-8 w-56 max-w-full" />
                </div>
                <div class="space-y-8">
                    <div v-for="i in 2" :key="i" class="d-surface p-5 sm:p-6">
                        <div
                            class="flex flex-wrap items-baseline justify-between gap-2"
                        >
                            <div class="d-skeleton h-5 w-48" />
                            <div class="d-skeleton h-3 w-24" />
                        </div>
                        <div class="d-skeleton mt-3 h-3 w-32" />
                        <div class="d-skeleton mt-4 h-4 w-full" />
                        <div class="d-skeleton mt-2 h-4 w-2/3" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Projects -->
        <section class="d-rule">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
                <div class="mb-10 flex items-end justify-between">
                    <div>
                        <div class="d-skeleton h-3 w-16" />
                        <div class="d-skeleton mt-3 h-8 w-56 max-w-full" />
                    </div>
                    <div class="d-skeleton hidden h-4 w-32 sm:block" />
                </div>
                <div class="grid gap-4 lg:grid-cols-12">
                    <div class="d-surface lg:col-span-7">
                        <div class="d-skeleton aspect-[16/9] w-full" />
                        <div class="p-5 sm:p-6">
                            <div
                                class="flex items-baseline justify-between gap-2"
                            >
                                <div class="d-skeleton h-5 w-40" />
                                <div class="d-skeleton h-3 w-10" />
                            </div>
                            <div class="d-skeleton mt-3 h-4 w-full" />
                            <div class="d-skeleton mt-2 h-4 w-3/4" />
                            <div class="d-skeleton mt-4 h-4 w-28" />
                        </div>
                    </div>
                    <div class="d-surface lg:col-span-5">
                        <div class="d-skeleton aspect-[16/9] w-full" />
                        <div class="p-5 sm:p-6">
                            <div class="d-skeleton h-5 w-36" />
                            <div class="d-skeleton mt-3 h-4 w-full" />
                            <div class="d-skeleton mt-4 h-4 w-24" />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
