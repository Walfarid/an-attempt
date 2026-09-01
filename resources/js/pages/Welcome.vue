<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3';
import { defineAsyncComponent, computed, ref } from 'vue';
import HeroIllustration from '@/components/site/HeroIllustration.vue';
import HomeSkeleton from '@/components/site/HomeSkeleton.vue';
import {
    ArrowUpRight,
    Briefcase,
    Mail,
    MapPin,
    Sparkles,
} from '@/components/site/icons';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import { useCountUp } from '@/composables/useCountUp';
import { useHeroScene } from '@/composables/useHeroScene';
import { useScrollAnimations } from '@/composables/useScrollAnimations';
import type {
    Education,
    Experience,
    Profile,
    Project,
    Publication,
    Skill,
    PublicPost,
} from '@/data/portfolio';

// Below-the-fold sections (skills, experience, projects, writing, education)
// live in their own chunk. The chunk is warmed after the first frame is
// painted (double rAF — the first callback runs in the same rendering step
// as the initial paint, the second strictly after it), so its parse never
// extends the boot chain, yet it is finished long before the deferred
// Inertia props land. The sections only mount once both are available, so
// the skeleton is replaced exactly as before.
const loadHomeSections = () => import('@/components/site/HomeSections.vue');
const HomeSections = defineAsyncComponent(loadHomeSections);
const sectionsChunkReady = ref(false);

function warmHomeSections() {
    loadHomeSections()
        .then(() => {
            sectionsChunkReady.value = true;
        })
        .catch(() => {
            // Chunk fetch failure — keep the skeleton rather than a blank page.
        });
}

requestAnimationFrame(() => {
    requestAnimationFrame(warmHomeSections);
});

const props = defineProps<{
    profile: Profile;
    stats: {
        years_active: number;
        projects_count: number;
        skills_count: number;
    };
    contact_email: string;
    // Deferred: absent on the first paint, resolved by the follow-up request.
    skills?: Skill[];
    experiences?: Experience[];
    projects?: Project[];
    educations?: Education[];
    publications?: Publication[];
    posts?: PublicPost[];
}>();

const mailtoHref = computed(() => {
    const subject = encodeURIComponent('Hello from your website');
    const body = encodeURIComponent(
        'Hi,\n\nI came across your portfolio and would like to get in touch.\n\n',
    );

    return `mailto:${props.contact_email}?subject=${subject}&body=${body}`;
});

/* Scroll animations -------------------------------------------------- */

const siteRef = ref<HTMLElement | null>(null);
const { refresh: refreshMotion } = useScrollAnimations(siteRef);

// HomeSections (async chunk) emits `contentMounted` once its DOM is
// committed — that is the moment the deferred sections land, and the
// refresh makes their data-motion elements reveal like the hero's.

/* Hero entrance + pointer depth, and stat count-ups ------------------ */

useHeroScene(siteRef);
useCountUp(siteRef);
</script>

<template>
    <Head :title="profile.name">
        <meta name="description" :content="profile.headline" />
        <meta property="og:title" :content="profile.name" />
        <meta property="og:description" :content="profile.headline" />
        <meta property="og:type" content="website" />
        <meta name="twitter:title" :content="profile.name" />
        <meta name="twitter:description" :content="profile.headline" />
    </Head>

    <div
        ref="siteRef"
        class="site d-dots-bg min-h-dvh antialiased selection:bg-(--accent) selection:text-(--paper)"
    >
        <!-- Skip link -->
        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:inline-flex focus:min-h-11 focus:border focus:border-(--ink) focus:bg-(--accent) focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-(--paper)"
        >
            Skip to main content
        </a>

        <SiteHeader />

        <main id="main">
            <!-- ═══════════════════ HERO ═══════════════════ -->
            <section
                aria-labelledby="hero-title"
                class="relative overflow-hidden"
            >
                <div
                    class="mx-auto max-w-6xl px-4 pt-16 pb-20 sm:px-6 sm:pt-24 sm:pb-28"
                >
                    <div
                        class="grid items-center gap-12 lg:grid-cols-[1.2fr_1fr]"
                    >
                        <!-- Text column -->
                        <div>
                            <p class="d-section hero-tagline mb-4">
                                {{ profile.headline }}
                            </p>
                            <h1
                                id="hero-title"
                                class="hero-name font-display text-[clamp(2.75rem,7vw,5.5rem)] leading-[0.95] font-bold tracking-tight text-balance"
                            >
                                {{ profile.name
                                }}<span class="text-(--accent)">.</span>
                            </h1>
                            <!-- eslint-disable-next-line vue/no-v-html — server-rendered sanitized Markdown -->
                            <div
                                v-if="profile.bio_html"
                                class="prose-site hero-tagline mt-6 max-w-lg text-base leading-relaxed"
                                v-html="profile.bio_html"
                            />
                            <div class="hero-cta mt-8 flex flex-wrap gap-3">
                                <a
                                    href="#projects"
                                    class="d-press d-arrow-link inline-flex min-h-11 items-center gap-2 bg-(--accent) px-6 py-3 text-sm font-semibold text-(--paper) no-underline transition-colors hover:bg-(--accent-hover)"
                                >
                                    View work
                                    <ArrowUpRight
                                        class="d-arrow-icon size-4"
                                        aria-hidden="true"
                                    />
                                </a>
                                <a
                                    href="#contact"
                                    class="d-press d-arrow-link d-surface d-hover inline-flex min-h-11 items-center gap-2 px-6 py-3 text-sm font-semibold no-underline"
                                >
                                    Get in touch
                                    <ArrowUpRight
                                        class="d-arrow-icon size-4"
                                        aria-hidden="true"
                                    />
                                </a>
                            </div>
                        </div>

                        <!-- Hero illustration (unDraw "Programming", recolored) -->
                        <div
                            class="hero-scene relative hidden lg:block"
                            aria-hidden="true"
                        >
                            <HeroIllustration />
                        </div>
                    </div>

                    <!-- Quick stats row -->
                    <div
                        class="mt-16 flex flex-wrap items-center gap-8 sm:gap-12"
                        data-motion-group
                    >
                        <div data-motion class="text-center">
                            <p
                                class="font-display text-3xl font-bold tabular-nums sm:text-4xl"
                            >
                                <span :data-count-to="stats.years_active">{{
                                    stats.years_active
                                }}</span
                                ><span aria-hidden="true">+</span>
                            </p>
                            <p class="d-label mt-1">Years shipping</p>
                        </div>
                        <div class="h-8 w-px bg-(--rule)" aria-hidden="true" />
                        <div data-motion class="text-center">
                            <p
                                class="font-display text-3xl font-bold tabular-nums sm:text-4xl"
                            >
                                <span :data-count-to="stats.projects_count">{{
                                    stats.projects_count
                                }}</span>
                            </p>
                            <p class="d-label mt-1">Projects built</p>
                        </div>
                        <div class="h-8 w-px bg-(--rule)" aria-hidden="true" />
                        <div data-motion class="text-center">
                            <p
                                class="font-display text-3xl font-bold tabular-nums sm:text-4xl"
                            >
                                <span :data-count-to="stats.skills_count">{{
                                    stats.skills_count
                                }}</span>
                            </p>
                            <p class="d-label mt-1">Technologies</p>
                        </div>
                        <div
                            v-if="profile.location"
                            data-motion
                            class="d-ink-soft ml-auto flex items-center gap-1.5"
                        >
                            <MapPin class="size-3.5" aria-hidden="true" />
                            <span class="text-sm">{{ profile.location }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Below-the-fold sections stream in after the hero paints. -->
            <Deferred
                :data="[
                    'skills',
                    'experiences',
                    'projects',
                    'educations',
                    'publications',
                    'posts',
                ]"
            >
                <template #fallback>
                    <HomeSkeleton />
                </template>
                <template #default="{ reloading }">
                    <HomeSections
                        v-if="sectionsChunkReady"
                        :profile="profile"
                        :reloading="reloading"
                        :skills="props.skills"
                        :experiences="props.experiences"
                        :projects="props.projects"
                        :educations="props.educations"
                        :publications="props.publications"
                        :posts="props.posts"
                        @content-mounted="refreshMotion"
                    />
                    <!-- The skeleton stays until the sections chunk is ready
                         (after the deferred props land), so there is never a
                         blank gap between prop arrival and section mount. -->
                    <HomeSkeleton v-else />
                </template>
            </Deferred>

            <!-- ═══════════════════ CONTACT ═══════════════════ -->
            <section
                id="contact"
                aria-labelledby="contact-title"
                class="d-rule scroll-mt-20"
            >
                <div
                    class="mx-auto grid max-w-6xl gap-8 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-[1fr_1.4fr]"
                    data-motion-group
                >
                    <div data-motion>
                        <p class="d-section mb-2">Contact</p>
                        <h2
                            id="contact-title"
                            class="font-display text-3xl font-bold tracking-tight sm:text-4xl"
                        >
                            Let's talk
                        </h2>
                        <p class="mt-3 max-w-md leading-relaxed text-pretty">
                            Got a project in mind, a question, or just want to
                            say hi? Drop a message — I read everything.
                        </p>
                        <div class="mt-6 flex flex-col gap-2">
                            <a
                                v-if="profile.github_url"
                                :href="profile.github_url"
                                target="_blank"
                                rel="noreferrer noopener"
                                class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold no-underline"
                            >
                                <Briefcase
                                    class="size-4 text-(--accent)"
                                    aria-hidden="true"
                                />
                                GitHub
                                <ArrowUpRight
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                            </a>
                            <a
                                v-if="profile.linkedin_url"
                                :href="profile.linkedin_url"
                                target="_blank"
                                rel="noreferrer noopener"
                                class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold no-underline"
                            >
                                <Sparkles
                                    class="size-4 text-(--accent)"
                                    aria-hidden="true"
                                />
                                LinkedIn
                                <ArrowUpRight
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                            </a>
                        </div>
                    </div>

                    <div class="d-surface grid gap-5 p-5 sm:p-8" data-motion>
                        <p class="leading-relaxed text-pretty">
                            The quickest way to reach me — click the button
                            below to open your email client.
                        </p>
                        <a
                            :href="mailtoHref"
                            class="d-press d-arrow-link inline-flex min-h-11 items-center gap-2 self-start bg-(--accent) px-6 py-2.5 text-sm font-semibold text-(--paper) no-underline transition-colors hover:bg-(--accent-hover)"
                        >
                            <Mail class="size-4" aria-hidden="true" />
                            Send email
                            <ArrowUpRight
                                class="d-arrow-icon size-4"
                                aria-hidden="true"
                            />
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <SiteFooter :profile="profile" />
    </div>
</template>
