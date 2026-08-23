<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, ExternalLink, MapPin, Plus } from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import type {
    Education,
    Experience,
    Profile,
    Project,
    Publication,
    PublicPost,
    Skill,
} from '@/data/portfolio';
import { formatDateRange } from '@/data/portfolio';
import contactRoute from '@/routes/contact';
import { index as blogIndex, show as postShow } from '@/routes/posts';

const {
    profile,
    experiences,
    skills,
    projects,
    educations,
    publications,
    posts,
    turnstile_site_key: turnstileSiteKey,
} = defineProps<{
    profile: Profile;
    experiences: Experience[];
    skills: Skill[];
    projects: Project[];
    educations: Education[];
    publications: Publication[];
    posts: PublicPost[];
    turnstile_site_key?: string | null;
}>();

/* Derived stats ----------------------------------------------------- */

function yearsActive(): string {
    if (!experiences.length) {
        return '0+';
    }

    const starts = experiences.map((e) => new Date(e.started_at).getTime());
    const ends = experiences.map((e) =>
        e.ended_at ? new Date(e.ended_at).getTime() : Date.now(),
    );
    const months =
        (Math.max(...ends) - Math.min(...starts)) /
        (1000 * 60 * 60 * 24 * 30.44);

    return `${Math.max(1, Math.round(months / 12))}+`;
}

const projectSpans = ['lg:col-span-7', 'lg:col-span-5'];

/* Turnstile ---------------------------------------------------------- */

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

/* Motion ------------------------------------------------------------
 *
 * One consistent reveal system for the whole page: every animated
 * block carries `data-motion`. Blocks inside a `data-motion-group`
 * cascade with a stagger when the group enters the viewport; standalone
 * blocks reveal individually. Same distance, duration, and easing
 * everywhere.
 */

const MOTION_DISTANCE = 24;
const MOTION_DURATION = 0.5;
const MOTION_STAGGER_CAP = 0.6;

const siteRef = ref<HTMLElement | null>(null);
let mm: gsap.MatchMedia | null = null;
let motionDisposed = false;

onMounted(async () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const [{ gsap }, { ScrollTrigger }] = await Promise.all([
        import('gsap'),
        import('gsap/ScrollTrigger'),
    ]);

    if (motionDisposed || !siteRef.value) {
        return;
    }

    gsap.registerPlugin(ScrollTrigger);
    mm = gsap.matchMedia(siteRef.value);

    mm.add(
        {
            motionOK: '(prefers-reduced-motion: no-preference)',
        },
        (context) => {
            const { motionOK } = context.conditions ?? {};

            if (!motionOK) {
                return;
            }

            const tweenFor = (
                targets: gsap.TweenTarget,
                trigger: HTMLElement,
                stagger: number,
            ) =>
                gsap.fromTo(
                    targets,
                    { y: MOTION_DISTANCE, autoAlpha: 0 },
                    {
                        y: 0,
                        autoAlpha: 1,
                        duration: MOTION_DURATION,
                        ease: 'power3.out',
                        stagger,
                        scrollTrigger: {
                            trigger,
                            start: 'top 88%',
                            once: true,
                        },
                    },
                );

            // Groups: direct children cascade together on one shared trigger.
            gsap.utils
                .toArray<HTMLElement>('[data-motion-group]')
                .forEach((group) => {
                    const items = Array.from(
                        group.querySelectorAll<HTMLElement>(
                            ':scope > [data-motion]',
                        ),
                    );

                    if (!items.length) {
                        return;
                    }

                    tweenFor(
                        items,
                        group,
                        Math.min(0.09, MOTION_STAGGER_CAP / items.length),
                    );
                });

            // Standalone blocks reveal individually.
            gsap.utils
                .toArray<HTMLElement>('[data-motion]')
                .forEach((element) => {
                    if (element.closest('[data-motion-group]')) {
                        return;
                    }

                    tweenFor(element, element, 0);
                });
        },
    );
});

onUnmounted(() => {
    motionDisposed = true;
    mm?.revert();
    mm = null;
});
</script>

<template>
    <Head :title="profile.name" />

    <div
        ref="siteRef"
        class="site min-h-dvh antialiased selection:bg-(--site-accent) selection:text-(--site-on-accent)"
    >
        <!-- Skip link -->
        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:inline-flex focus:min-h-11 focus:border-2 focus:border-(--site-ink) focus:bg-(--site-accent) focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-(--site-on-accent)"
        >
            Skip to main content
        </a>

        <SiteHeader />

        <main id="main">
            <!-- Hero bento -->
            <section
                aria-labelledby="hero-title"
                class="mx-auto max-w-6xl px-4 pt-8 pb-14 sm:px-6 sm:pt-12"
            >
                <div class="grid gap-4 lg:grid-cols-12" data-motion-group>
                    <!-- Headline cell -->
                    <div
                        class="b-panel b-shadow-md p-6 sm:p-10 lg:col-span-7"
                        data-motion
                    >
                        <p
                            class="b-label mb-5 inline-block border-2 border-(--site-ink) bg-(--site-panel-inset) px-2.5 py-1"
                        >
                            {{ profile.headline }}
                        </p>
                        <h1
                            id="hero-title"
                            class="font-display text-[clamp(2.25rem,5.5vw,4rem)] leading-[1.02] font-bold tracking-tight text-balance"
                        >
                            Calm, reliable software — from API to production.
                        </h1>
                        <!-- eslint-disable-next-line vue/no-v-html — server-rendered sanitized Markdown -->
                        <div
                            v-if="profile.bio_html"
                            class="prose-site mt-5 max-w-lg text-base leading-relaxed text-(--site-text-secondary) sm:text-lg"
                            v-html="profile.bio_html"
                        />
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a
                                href="#projects"
                                class="b-shadow-sm b-lift inline-flex min-h-11 items-center gap-2 border-2 border-(--site-ink) bg-(--site-accent) px-5 py-2.5 text-sm font-semibold text-(--site-on-accent) no-underline"
                            >
                                View work
                                <ArrowUpRight
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </a>
                            <a
                                href="#contact"
                                class="b-shadow-sm b-lift inline-flex min-h-11 items-center gap-2 border-2 border-(--site-ink) bg-(--site-panel) px-5 py-2.5 text-sm font-semibold no-underline"
                            >
                                Contact
                            </a>
                        </div>
                    </div>

                    <!-- Status cell -->
                    <div
                        class="b-panel b-shadow-md flex flex-col justify-between gap-6 p-6 sm:p-8 lg:col-span-5"
                        data-motion
                    >
                        <div class="flex items-start justify-between gap-4">
                            <p
                                class="b-label inline-flex items-center gap-2 border-2 border-(--site-ink) bg-(--site-accent) px-2.5 py-1 text-(--site-on-accent)"
                            >
                                {{ profile.name }}
                            </p>
                            <p
                                v-if="profile.location"
                                class="b-label inline-flex items-center gap-1.5 text-(--site-text-secondary)"
                            >
                                <MapPin class="size-3.5" aria-hidden="true" />
                                {{ profile.location }}
                            </p>
                        </div>
                        <div>
                            <p class="b-label text-(--site-text-secondary)">
                                Currently
                            </p>
                            <p
                                class="mt-1.5 text-lg leading-snug font-semibold"
                            >
                                {{ educations[0]?.degree ?? profile.headline }}
                            </p>
                            <p
                                v-if="educations[0]"
                                class="mt-1 text-sm text-(--site-text-secondary)"
                            >
                                {{ educations[0].school }}
                            </p>
                        </div>
                        <div
                            class="flex flex-col gap-2 border-t-2 border-(--site-ink) pt-4"
                        >
                            <a
                                v-if="profile.github_url"
                                :href="profile.github_url"
                                target="_blank"
                                rel="noreferrer noopener"
                                class="group inline-flex min-h-11 items-center justify-between gap-2 font-mono text-sm font-medium no-underline"
                            >
                                <span class="inline-flex items-center gap-2">
                                    GitHub
                                </span>
                                <ArrowUpRight
                                    class="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none"
                                    aria-hidden="true"
                                />
                            </a>
                            <a
                                v-if="profile.linkedin_url"
                                :href="profile.linkedin_url"
                                target="_blank"
                                rel="noreferrer noopener"
                                class="group inline-flex min-h-11 items-center justify-between gap-2 font-mono text-sm font-medium no-underline"
                            >
                                <span class="inline-flex items-center gap-2">
                                    LinkedIn
                                </span>
                                <ArrowUpRight
                                    class="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none"
                                    aria-hidden="true"
                                />
                            </a>
                        </div>
                    </div>

                    <!-- Stat cells -->
                    <div
                        v-for="stat in [
                            { value: yearsActive(), label: 'Years shipping' },
                            {
                                value: String(projects.length),
                                label: 'Projects built',
                            },
                            {
                                value: String(skills.length),
                                label: 'Technologies',
                            },
                        ]"
                        :key="stat.label"
                        class="b-panel b-shadow-sm p-5 lg:col-span-4"
                        data-motion
                    >
                        <p
                            class="font-mono text-3xl font-semibold tabular-nums"
                        >
                            {{ stat.value }}
                        </p>
                        <p class="b-label mt-1 text-(--site-text-secondary)">
                            {{ stat.label }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Experience -->
            <section
                v-if="experiences.length"
                id="experience"
                aria-labelledby="experience-title"
                class="scroll-mt-20 border-t-2 border-(--site-ink)"
            >
                <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
                    <div
                        class="mb-10 flex flex-wrap items-end justify-between gap-4"
                        data-motion
                    >
                        <div>
                            <p class="b-label mb-2 text-(--site-accent)">
                                Experience
                            </p>
                            <h2
                                id="experience-title"
                                class="text-3xl font-bold tracking-tight sm:text-4xl"
                            >
                                Where I've been
                            </h2>
                        </div>
                    </div>

                    <ol class="space-y-4" data-motion-group>
                        <li
                            v-for="(exp, i) in experiences"
                            :key="exp.id"
                            data-motion
                        >
                            <article
                                class="b-panel b-shadow-sm b-lift p-6 sm:p-8"
                            >
                                <div
                                    class="grid gap-x-8 gap-y-4 sm:grid-cols-[auto_1fr]"
                                >
                                    <span
                                        class="b-label flex size-12 items-center justify-center self-start border-2 border-(--site-ink) bg-(--site-panel-inset)"
                                        aria-hidden="true"
                                    >
                                        {{ String(i + 1).padStart(2, '0') }}
                                    </span>
                                    <div>
                                        <div
                                            class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between"
                                        >
                                            <h3
                                                class="text-xl font-bold tracking-tight"
                                            >
                                                {{ exp.role }}
                                            </h3>
                                            <time
                                                class="b-label shrink-0 text-(--site-text-secondary)"
                                            >
                                                {{
                                                    formatDateRange(
                                                        exp.started_at,
                                                        exp.ended_at,
                                                    )
                                                }}
                                            </time>
                                        </div>
                                        <p
                                            class="mt-0.5 text-sm font-medium text-(--site-accent)"
                                        >
                                            {{ exp.company }}
                                            <span
                                                v-if="exp.location"
                                                class="font-normal text-(--site-text-secondary)"
                                            >
                                                · {{ exp.location }}
                                            </span>
                                        </p>
                                        <p
                                            class="mt-3 max-w-2xl leading-relaxed text-pretty text-(--site-text-secondary)"
                                        >
                                            {{ exp.summary }}
                                        </p>
                                        <ul
                                            class="mt-4 grid max-w-2xl gap-x-6 gap-y-2 sm:grid-cols-2"
                                        >
                                            <li
                                                v-for="highlight in exp.highlights"
                                                :key="highlight"
                                                class="flex items-start gap-2 text-sm text-(--site-text-secondary)"
                                            >
                                                <Plus
                                                    class="mt-0.5 size-3.5 shrink-0 text-(--site-accent)"
                                                    aria-hidden="true"
                                                />
                                                {{ highlight }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </article>
                        </li>
                    </ol>
                </div>
            </section>

            <!-- Projects -->
            <section
                id="projects"
                aria-labelledby="projects-title"
                class="scroll-mt-20 border-t-2 border-(--site-ink)"
            >
                <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
                    <div
                        class="mb-10 flex flex-wrap items-end justify-between gap-4"
                        data-motion
                    >
                        <div>
                            <p class="b-label mb-2 text-(--site-accent)">
                                Projects
                            </p>
                            <h2
                                id="projects-title"
                                class="text-3xl font-bold tracking-tight sm:text-4xl"
                            >
                                Selected work
                            </h2>
                        </div>
                        <a
                            v-if="profile.github_url"
                            :href="profile.github_url"
                            target="_blank"
                            rel="noreferrer noopener"
                            class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold no-underline"
                        >
                            More on GitHub
                            <ExternalLink class="size-4" aria-hidden="true" />
                            <span class="sr-only">(opens in a new tab)</span>
                        </a>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-12" data-motion-group>
                        <article
                            v-for="(project, i) in projects"
                            :key="project.id"
                            class="b-panel b-shadow-md b-lift flex flex-col"
                            :class="projectSpans[i % projectSpans.length]"
                            data-motion
                        >
                            <img
                                v-if="project.screenshots[0]?.url"
                                :src="project.screenshots[0].url"
                                :alt="
                                    project.screenshots[0].alt ?? project.title
                                "
                                class="aspect-video w-full border-b-2 border-(--site-ink) object-cover"
                            />
                            <div
                                v-else
                                class="relative border-b-2 border-(--site-ink) p-6"
                                :class="
                                    i % 2 === 0
                                        ? 'bg-[repeating-linear-gradient(-45deg,var(--site-accent-soft)_0_14px,transparent_14px_28px)]'
                                        : 'bg-[repeating-linear-gradient(45deg,var(--site-accent-soft)_0_14px,transparent_14px_28px)]'
                                "
                                aria-hidden="true"
                            >
                                <span
                                    class="b-label inline-block border-2 border-(--site-ink) bg-(--site-panel) px-2 py-1"
                                >
                                    {{ project.year }}
                                </span>
                            </div>
                            <div class="flex flex-1 flex-col gap-4 p-6 sm:p-8">
                                <h3 class="text-xl font-bold tracking-tight">
                                    {{ project.title }}
                                </h3>
                                <p
                                    class="max-w-prose leading-relaxed text-pretty text-(--site-text-secondary)"
                                >
                                    {{ project.description }}
                                </p>
                                <ul class="flex flex-wrap gap-2">
                                    <li
                                        v-for="skill in project.skills"
                                        :key="skill.id"
                                        class="b-label border-2 border-(--site-ink) bg-(--site-panel-inset) px-2 py-1"
                                    >
                                        {{ skill.name }}
                                    </li>
                                </ul>
                                <div
                                    class="mt-auto flex flex-wrap gap-3 border-t-2 border-(--site-ink) pt-4"
                                >
                                    <a
                                        v-if="project.live_url"
                                        :href="project.live_url"
                                        target="_blank"
                                        rel="noreferrer noopener"
                                        class="b-shadow-sm b-lift inline-flex min-h-11 items-center gap-1.5 border-2 border-(--site-ink) bg-(--site-accent) px-4 py-2 text-sm font-semibold text-(--site-on-accent) no-underline"
                                    >
                                        Live site
                                        <ArrowUpRight
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                        <span class="sr-only"
                                            >(opens in a new tab)</span
                                        >
                                    </a>
                                    <a
                                        v-if="project.repo_url"
                                        :href="project.repo_url"
                                        target="_blank"
                                        rel="noreferrer noopener"
                                        class="b-shadow-sm b-lift inline-flex min-h-11 items-center gap-1.5 border-2 border-(--site-ink) bg-(--site-panel) px-4 py-2 text-sm font-semibold no-underline"
                                    >
                                        Source
                                        <ExternalLink
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                        <span class="sr-only"
                                            >(opens in a new tab)</span
                                        >
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Skills -->
            <section
                v-if="skills.length"
                id="skills"
                aria-labelledby="skills-title"
                class="scroll-mt-20 border-t-2 border-(--site-ink)"
            >
                <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
                    <div class="mb-10 max-w-2xl" data-motion>
                        <p class="b-label mb-2 text-(--site-accent)">Skills</p>
                        <h2
                            id="skills-title"
                            class="text-3xl font-bold tracking-tight sm:text-4xl"
                        >
                            Tools &amp; technologies
                        </h2>
                    </div>

                    <dl
                        class="grid grid-cols-2 gap-px border-2 border-(--site-ink) bg-(--site-ink) sm:grid-cols-3 lg:grid-cols-4"
                        data-motion-group
                    >
                        <div
                            v-for="skill in skills"
                            :key="skill.id"
                            class="flex flex-col gap-1 bg-(--site-panel) p-4 sm:p-5"
                            data-motion
                        >
                            <dt class="truncate text-sm font-semibold">
                                {{ skill.name }}
                            </dt>
                            <dd
                                class="b-label text-(--site-text-secondary) capitalize"
                            >
                                {{ skill.category }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <!-- Writing teaser -->
            <section
                v-if="posts.length"
                id="writing"
                aria-labelledby="writing-title"
                class="scroll-mt-20 border-t-2 border-(--site-ink)"
            >
                <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
                    <div
                        class="mb-10 flex flex-wrap items-end justify-between gap-4"
                        data-motion
                    >
                        <div>
                            <p class="b-label mb-2 text-(--site-accent)">
                                Writing
                            </p>
                            <h2
                                id="writing-title"
                                class="text-3xl font-bold tracking-tight sm:text-4xl"
                            >
                                Recent notes
                            </h2>
                        </div>
                        <Link
                            :href="blogIndex()"
                            class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold no-underline"
                        >
                            All posts
                            <ArrowUpRight class="size-4" aria-hidden="true" />
                        </Link>
                    </div>

                    <ol class="grid gap-4 md:grid-cols-3" data-motion-group>
                        <li v-for="post in posts" :key="post.id" data-motion>
                            <Link
                                :href="postShow.url({ post: post.slug })"
                                class="b-panel b-shadow-sm b-lift flex h-full flex-col p-6 no-underline"
                            >
                                <time
                                    class="b-label text-(--site-text-secondary)"
                                >
                                    {{
                                        new Date(
                                            post.published_at,
                                        ).toLocaleDateString('en-US', {
                                            month: 'short',
                                            day: 'numeric',
                                            year: 'numeric',
                                        })
                                    }}
                                </time>
                                <h3
                                    class="mt-3 text-lg leading-snug font-bold tracking-tight"
                                >
                                    {{ post.title }}
                                </h3>
                                <p
                                    class="mt-2 line-clamp-3 text-sm leading-relaxed text-(--site-text-secondary)"
                                >
                                    {{ post.teaser_text ?? post.excerpt }}
                                </p>
                                <span
                                    class="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-semibold text-(--site-accent)"
                                    >Read
                                    <ArrowUpRight
                                        class="size-3.5"
                                        aria-hidden="true"
                                /></span>
                            </Link>
                        </li>
                    </ol>
                </div>
            </section>

            <!-- Education & publications -->
            <section
                v-if="educations.length || publications.length"
                id="education"
                aria-labelledby="education-title"
                class="scroll-mt-20 border-t-2 border-(--site-ink)"
            >
                <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
                    <div class="mb-10 max-w-2xl" data-motion>
                        <p class="b-label mb-2 text-(--site-accent)">
                            Education
                        </p>
                        <h2
                            id="education-title"
                            class="text-3xl font-bold tracking-tight sm:text-4xl"
                        >
                            Academic grounding
                        </h2>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2" data-motion-group>
                        <article
                            v-for="edu in educations"
                            :key="edu.id"
                            class="b-panel b-shadow-md b-lift flex flex-col gap-3 p-6 sm:p-8"
                            data-motion
                        >
                            <div
                                class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between"
                            >
                                <h3 class="text-xl font-bold tracking-tight">
                                    {{ edu.school }}
                                </h3>
                                <time
                                    class="b-label shrink-0 text-(--site-text-secondary)"
                                >
                                    {{
                                        edu.started_at
                                            ? formatDateRange(
                                                  edu.started_at,
                                                  edu.ended_at,
                                              )
                                            : edu.ended_at
                                              ? new Date(
                                                    edu.ended_at,
                                                ).toLocaleDateString('en-US', {
                                                    month: 'short',
                                                    year: 'numeric',
                                                })
                                              : 'Present'
                                    }}
                                </time>
                            </div>
                            <p class="text-sm font-medium text-(--site-accent)">
                                {{ edu.degree }}
                            </p>
                            <ul
                                v-if="edu.details.length"
                                class="mt-1 grid gap-2"
                            >
                                <li
                                    v-for="detail in edu.details"
                                    :key="detail"
                                    class="flex items-start gap-2 text-sm text-(--site-text-secondary)"
                                >
                                    <Plus
                                        class="mt-0.5 size-3.5 shrink-0 text-(--site-accent)"
                                        aria-hidden="true"
                                    />
                                    {{ detail }}
                                </li>
                            </ul>
                        </article>
                    </div>

                    <article
                        v-for="pub in publications"
                        :key="pub.id"
                        class="b-panel b-shadow-sm mt-4 p-6"
                        data-motion
                    >
                        <p class="b-label text-(--site-text-secondary)">
                            Publication · {{ pub.year }} · {{ pub.venue }}
                        </p>
                        <p class="mt-2 max-w-3xl leading-relaxed text-pretty">
                            {{ pub.citation }}
                        </p>
                        <a
                            :href="pub.doi_url"
                            target="_blank"
                            rel="noreferrer noopener"
                            class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold no-underline"
                        >
                            Read the paper (DOI)
                            <ExternalLink class="size-4" aria-hidden="true" />
                            <span class="sr-only">(opens in a new tab)</span>
                        </a>
                    </article>
                </div>
            </section>

            <!-- Contact -->
            <section
                id="contact"
                aria-labelledby="contact-title"
                class="scroll-mt-20 border-t-2 border-(--site-ink)"
            >
                <div
                    class="mx-auto grid max-w-6xl gap-8 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[1fr_1.4fr]"
                    data-motion-group
                >
                    <div data-motion>
                        <p class="b-label mb-2 text-(--site-accent)">Contact</p>
                        <h2
                            id="contact-title"
                            class="text-3xl font-bold tracking-tight sm:text-4xl"
                        >
                            Get in touch
                        </h2>
                        <p
                            class="mt-3 max-w-md leading-relaxed text-pretty text-(--site-text-secondary)"
                        >
                            Questions, ideas, opportunities — the form goes
                            straight to my inbox.
                        </p>
                    </div>

                    <Form
                        v-bind="contactRoute.store.form()"
                        #default="{ errors, processing }"
                        class="b-panel b-shadow-md grid gap-4 p-6 sm:p-8"
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
                                <label for="contact-name" class="b-label"
                                    >Name</label
                                >
                                <input
                                    id="contact-name"
                                    name="name"
                                    required
                                    maxlength="255"
                                    placeholder="Ada Lovelace"
                                    class="min-h-11 border-2 border-(--site-ink) bg-(--site-panel-raised) px-3 py-2 text-sm outline-none focus:border-(--site-accent) focus:ring-2 focus:ring-(--site-accent)/30"
                                />
                                <InputError :message="errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <label for="contact-email" class="b-label"
                                    >Email</label
                                >
                                <input
                                    id="contact-email"
                                    name="email"
                                    type="email"
                                    required
                                    maxlength="255"
                                    placeholder="you@example.com"
                                    class="min-h-11 border-2 border-(--site-ink) bg-(--site-panel-raised) px-3 py-2 text-sm outline-none focus:border-(--site-accent) focus:ring-2 focus:ring-(--site-accent)/30"
                                />
                                <InputError :message="errors.email" />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <label for="contact-subject" class="b-label"
                                >Subject
                                <span class="opacity-60">(optional)</span>
                            </label>
                            <input
                                id="contact-subject"
                                name="subject"
                                maxlength="255"
                                placeholder="Hello!"
                                class="min-h-11 border-2 border-(--site-ink) bg-(--site-panel-raised) px-3 py-2 text-sm outline-none focus:border-(--site-accent) focus:ring-2 focus:ring-(--site-accent)/30"
                            />
                            <InputError :message="errors.subject" />
                        </div>
                        <div class="grid gap-2">
                            <label for="contact-body" class="b-label"
                                >Message</label
                            >
                            <textarea
                                id="contact-body"
                                name="body"
                                required
                                rows="5"
                                maxlength="5000"
                                placeholder="What's on your mind?"
                                class="border-2 border-(--site-ink) bg-(--site-panel-raised) px-3 py-2 text-sm outline-none focus:border-(--site-accent) focus:ring-2 focus:ring-(--site-accent)/30"
                            />
                            <InputError :message="errors.body" />
                        </div>
                        <div v-if="turnstileSiteKey" ref="captchaContainer" />
                        <button
                            type="submit"
                            class="b-shadow-sm b-lift mt-1 inline-flex min-h-11 items-center justify-center gap-2 border-2 border-(--site-ink) bg-(--site-accent) px-5 py-2.5 text-sm font-semibold text-(--site-on-accent) disabled:pointer-events-none disabled:opacity-50"
                            :disabled="processing"
                        >
                            Send message
                            <ArrowUpRight class="size-4" aria-hidden="true" />
                        </button>
                    </Form>
                </div>
            </section>
        </main>

        <SiteFooter :profile="profile" />
    </div>
</template>
