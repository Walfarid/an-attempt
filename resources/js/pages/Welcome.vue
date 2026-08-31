<script setup lang="ts">
import { Deferred, Form, Head } from '@inertiajs/vue3';
import { defineAsyncComponent, onMounted, onUnmounted, ref } from 'vue';
import { ArrowUpRight, Briefcase, MapPin, Sparkles } from '@/components/site/icons';
import InputError from '@/components/InputError.vue';
import HomeSkeleton from '@/components/site/HomeSkeleton.vue';
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
import contactRoute from '@/routes/contact';

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
    turnstile_site_key?: string | null;
    // Deferred: absent on the first paint, resolved by the follow-up request.
    skills?: Skill[];
    experiences?: Experience[];
    projects?: Project[];
    educations?: Education[];
    publications?: Publication[];
    posts?: PublicPost[];
}>();

/* Turnstile ---------------------------------------------------------- */

const turnstileSiteKey = props.turnstile_site_key;

declare global {
    interface Window {
        turnstile?: {
            render: (
                el: HTMLElement,
                options: { sitekey: string },
            ) => string | undefined;
        };
    }
}

const captchaContainer = ref<HTMLElement | null>(null);
let disposed = false;

onMounted(async () => {
    if (!turnstileSiteKey || !captchaContainer.value) {
        return;
    }

    const existing = document.querySelector<HTMLScriptElement>(
        'script[data-turnstile]',
    );

    if (existing) {
        existing.addEventListener('load', () =>
            window.turnstile?.render(captchaContainer.value!, {
                sitekey: turnstileSiteKey,
            }),
        );

        return;
    }

    const script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    script.async = true;
    script.defer = true;
    script.dataset.turnstile = '';
    script.addEventListener('load', () => {
        if (!disposed && captchaContainer.value) {
            window.turnstile?.render(captchaContainer.value, {
                sitekey: turnstileSiteKey,
            });
        }
    });
    document.head.appendChild(script);
});

onUnmounted(() => {
    disposed = true;
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

                        <!-- Abstract SVG illustration -->
                        <div
                            class="hero-scene relative hidden lg:block"
                            aria-hidden="true"
                        >
                            <svg
                                class="hero-svg-shape mx-auto w-full max-w-md"
                                viewBox="0 0 400 400"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <!-- Constellation of interconnected nodes -->
                                <g class="c-rings">
                                    <circle
                                        cx="200"
                                        cy="200"
                                        r="120"
                                        stroke="var(--rule)"
                                        stroke-width="0.5"
                                        stroke-dasharray="4 4"
                                    />
                                    <circle
                                        cx="200"
                                        cy="200"
                                        r="80"
                                        stroke="var(--rule)"
                                        stroke-width="0.5"
                                        stroke-dasharray="4 4"
                                    />
                                    <circle
                                        cx="200"
                                        cy="200"
                                        r="40"
                                        stroke="var(--accent)"
                                        stroke-width="1"
                                        opacity="0.3"
                                    />
                                </g>

                                <g class="c-lines">
                                    <line
                                        x1="120"
                                        y1="140"
                                        x2="200"
                                        y2="200"
                                        stroke="var(--accent)"
                                        stroke-width="0.75"
                                        opacity="0.4"
                                    />
                                    <line
                                        x1="280"
                                        y1="160"
                                        x2="200"
                                        y2="200"
                                        stroke="var(--accent)"
                                        stroke-width="0.75"
                                        opacity="0.4"
                                    />
                                    <line
                                        x1="160"
                                        y1="280"
                                        x2="200"
                                        y2="200"
                                        stroke="var(--accent)"
                                        stroke-width="0.75"
                                        opacity="0.4"
                                    />
                                    <line
                                        x1="300"
                                        y1="260"
                                        x2="200"
                                        y2="200"
                                        stroke="var(--accent)"
                                        stroke-width="0.75"
                                        opacity="0.4"
                                    />
                                    <line
                                        x1="120"
                                        y1="140"
                                        x2="280"
                                        y2="160"
                                        stroke="var(--rule)"
                                        stroke-width="0.5"
                                    />
                                    <line
                                        x1="280"
                                        y1="160"
                                        x2="300"
                                        y2="260"
                                        stroke="var(--rule)"
                                        stroke-width="0.5"
                                    />
                                    <line
                                        x1="160"
                                        y1="280"
                                        x2="120"
                                        y2="140"
                                        stroke="var(--rule)"
                                        stroke-width="0.5"
                                    />
                                </g>

                                <g class="c-nodes">
                                    <circle
                                        cx="200"
                                        cy="200"
                                        r="8"
                                        fill="var(--accent)"
                                    />
                                    <circle
                                        cx="120"
                                        cy="140"
                                        r="5"
                                        fill="var(--accent)"
                                        opacity="0.7"
                                    />
                                    <circle
                                        cx="280"
                                        cy="160"
                                        r="5"
                                        fill="var(--accent)"
                                        opacity="0.7"
                                    />
                                    <circle
                                        cx="160"
                                        cy="280"
                                        r="5"
                                        fill="var(--accent)"
                                        opacity="0.7"
                                    />
                                    <circle
                                        cx="300"
                                        cy="260"
                                        r="5"
                                        fill="var(--accent)"
                                        opacity="0.7"
                                    />
                                </g>

                                <g class="c-dots">
                                    <circle
                                        cx="90"
                                        cy="200"
                                        r="2"
                                        fill="var(--accent)"
                                        opacity="0.3"
                                    />
                                    <circle
                                        cx="310"
                                        cy="120"
                                        r="2"
                                        fill="var(--accent)"
                                        opacity="0.3"
                                    />
                                    <circle
                                        cx="240"
                                        cy="320"
                                        r="2"
                                        fill="var(--accent)"
                                        opacity="0.3"
                                    />
                                    <circle
                                        cx="100"
                                        cy="300"
                                        r="2"
                                        fill="var(--accent)"
                                        opacity="0.3"
                                    />
                                    <circle
                                        cx="340"
                                        cy="200"
                                        r="2"
                                        fill="var(--accent)"
                                        opacity="0.3"
                                    />
                                </g>

                                <g class="c-marks">
                                    <rect
                                        x="140"
                                        y="100"
                                        width="16"
                                        height="16"
                                        stroke="var(--accent)"
                                        stroke-width="0.75"
                                        fill="none"
                                        opacity="0.3"
                                        transform="rotate(15 148 108)"
                                    />
                                    <rect
                                        x="260"
                                        y="280"
                                        width="12"
                                        height="12"
                                        stroke="var(--accent)"
                                        stroke-width="0.75"
                                        fill="none"
                                        opacity="0.3"
                                        transform="rotate(-20 266 286)"
                                    />
                                </g>
                            </svg>
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

                    <Form
                        v-bind="contactRoute.store.form()"
                        #default="{ errors, processing }"
                        class="d-surface grid gap-4 p-5 sm:p-8"
                        reset-on-success
                        data-motion
                    >
                        <input
                            type="text"
                            name="website"
                            tabindex="-1"
                            autocomplete="off"
                            aria-hidden="true"
                            class="hidden"
                        />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <label for="contact-name" class="d-label"
                                    >Name</label
                                >
                                <input
                                    id="contact-name"
                                    name="name"
                                    required
                                    maxlength="255"
                                    autocomplete="name"
                                    placeholder="Ada Lovelace"
                                    class="min-h-11 border border-(--rule) bg-(--paper) px-3 py-2 text-sm outline-none focus:border-(--accent) focus:ring-1 focus:ring-(--accent)"
                                />
                                <InputError :message="errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <label for="contact-email" class="d-label"
                                    >Email</label
                                >
                                <input
                                    id="contact-email"
                                    name="email"
                                    type="email"
                                    required
                                    maxlength="255"
                                    autocomplete="email"
                                    placeholder="you@example.com"
                                    class="min-h-11 border border-(--rule) bg-(--paper) px-3 py-2 text-sm outline-none focus:border-(--accent) focus:ring-1 focus:ring-(--accent)"
                                />
                                <InputError :message="errors.email" />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <label for="contact-subject" class="d-label">
                                Subject
                                <span class="opacity-60">(optional)</span>
                            </label>
                            <input
                                id="contact-subject"
                                name="subject"
                                maxlength="255"
                                placeholder="Hello!"
                                class="min-h-11 border border-(--rule) bg-(--paper) px-3 py-2 text-sm outline-none focus:border-(--accent) focus:ring-1 focus:ring-(--accent)"
                            />
                            <InputError :message="errors.subject" />
                        </div>
                        <div class="grid gap-2">
                            <label for="contact-body" class="d-label"
                                >Message</label
                            >
                            <textarea
                                id="contact-body"
                                name="body"
                                required
                                rows="5"
                                maxlength="5000"
                                placeholder="What's on your mind?"
                                class="border border-(--rule) bg-(--paper) px-3 py-2 text-sm outline-none focus:border-(--accent) focus:ring-1 focus:ring-(--accent)"
                            />
                            <InputError :message="errors.body" />
                        </div>
                        <div v-if="turnstileSiteKey" ref="captchaContainer" />
                        <button
                            type="submit"
                            class="d-press mt-1 inline-flex min-h-11 items-center justify-center gap-2 bg-(--accent) px-6 py-2.5 text-sm font-semibold text-(--paper) transition-colors hover:bg-(--accent-hover) disabled:pointer-events-none disabled:opacity-50"
                            :disabled="processing"
                        >
                            Send message
                            <ArrowUpRight
                                class="d-arrow-icon size-4"
                                aria-hidden="true"
                            />
                        </button>
                    </Form>
                </div>
            </section>
        </main>

        <SiteFooter :profile="profile" />
    </div>
</template>
