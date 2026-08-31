<script setup lang="ts">
import { Deferred, Form, Head, Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    BookOpen,
    Briefcase,
    ChevronDown,
    Code2,
    Database,
    ExternalLink,
    GraduationCap,
    Layers,
    MapPin,
    Rocket,
    Shield,
    Sparkles,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
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
    SkillCategory,
    PublicPost,
} from '@/data/portfolio';
import { formatDateRange } from '@/data/portfolio';
import contactRoute from '@/routes/contact';
import { index as blogIndex, show as postShow } from '@/routes/posts';

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

/* Deferred sections — resolved server-side after the hero paints. ----- */

const sections = computed(() => ({
    skills: props.skills ?? [],
    experiences: props.experiences ?? [],
    projects: props.projects ?? [],
    educations: props.educations ?? [],
    publications: props.publications ?? [],
    posts: props.posts ?? [],
}));

/* Skills grouped by category (UX: no endless table) ------------------ */

const categoryMeta: Record<
    SkillCategory,
    { label: string; icon: typeof Code2; color: string }
> = {
    languages: { label: 'Languages', icon: Code2, color: '#17594A' },
    frameworks: { label: 'Frameworks', icon: Layers, color: '#3b7a57' },
    databases: { label: 'Databases', icon: Database, color: '#17594A' },
    devops: { label: 'DevOps', icon: Rocket, color: '#3b7a57' },
    platform: { label: 'Platform', icon: BookOpen, color: '#17594A' },
    security: { label: 'Security', icon: Shield, color: '#3b7a57' },
};

const skillsByCategory = computed(() => {
    const map = new Map<SkillCategory, Skill[]>();

    for (const skill of sections.value.skills) {
        // Category is always present on public homepage (HomeController includes it)
        if (!skill.category) {
            continue;
        }

        const group = map.get(skill.category);

        if (group) {
            group.push(skill);
        } else {
            map.set(skill.category, [skill]);
        }
    }

    return Array.from(map.entries()).map(([category, skills]) => ({
        category,
        skills,
    }));
});

const expandedCategories = ref<Set<string>>(new Set());

function toggleCategory(category: string) {
    const next = new Set(expandedCategories.value);

    if (next.has(category)) {
        next.delete(category);
    } else {
        next.add(category);
    }

    expandedCategories.value = next;
}

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

// Deferred sections mount after the initial scan; give their
// data-motion elements reveal animations once they arrive.
const sectionsArrived = ref(false);

watch(
    () => props.skills !== undefined && props.experiences !== undefined,
    (ready) => {
        if (ready && !sectionsArrived.value) {
            sectionsArrived.value = true;
            refreshMotion();
        }
    },
    { immediate: true },
);

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
                    <div
                        :class="{ 'opacity-60 transition-opacity': reloading }"
                    >
                        <!-- ═══════════════════ SKILLS (UX: grouped, expandable) ═══════════════════ -->
                        <section
                            v-if="sections.skills.length"
                            id="skills"
                            aria-labelledby="skills-title"
                            class="d-rule scroll-mt-20"
                        >
                            <div
                                class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24"
                            >
                                <div class="mb-10 max-w-2xl" data-motion>
                                    <p class="d-section mb-2">Skills</p>
                                    <h2
                                        id="skills-title"
                                        class="font-display text-3xl font-bold tracking-tight sm:text-4xl"
                                    >
                                        What I work with
                                    </h2>
                                    <p
                                        class="mt-3 text-base leading-relaxed text-(--ink-soft)"
                                    >
                                        Grouped so you don't have to scroll
                                        through a wall of text. Tap a category
                                        to see what's inside.
                                    </p>
                                </div>

                                <div
                                    class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                                    data-motion-group
                                >
                                    <div
                                        v-for="group in skillsByCategory"
                                        :key="group.category"
                                        class="d-surface transition-shadow hover:shadow-sm"
                                        data-motion
                                    >
                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-4 p-5 text-left"
                                            :aria-expanded="
                                                expandedCategories.has(
                                                    group.category,
                                                )
                                            "
                                            @click="
                                                toggleCategory(group.category)
                                            "
                                        >
                                            <span
                                                class="flex size-10 shrink-0 items-center justify-center bg-(--accent-soft)"
                                                aria-hidden="true"
                                            >
                                                <component
                                                    :is="
                                                        categoryMeta[
                                                            group.category
                                                        ].icon
                                                    "
                                                    class="size-5 text-(--accent)"
                                                />
                                            </span>
                                            <div class="flex-1">
                                                <p
                                                    class="font-display text-base font-semibold"
                                                >
                                                    {{
                                                        categoryMeta[
                                                            group.category
                                                        ].label
                                                    }}
                                                </p>
                                                <p class="d-label mt-0.5">
                                                    {{ group.skills.length }}
                                                    {{
                                                        group.skills.length ===
                                                        1
                                                            ? 'tool'
                                                            : 'tools'
                                                    }}
                                                </p>
                                            </div>
                                            <ChevronDown
                                                class="size-4 shrink-0 text-(--ink-soft) transition-transform"
                                                :class="{
                                                    'rotate-180':
                                                        expandedCategories.has(
                                                            group.category,
                                                        ),
                                                }"
                                                aria-hidden="true"
                                            />
                                        </button>
                                        <div
                                            v-show="
                                                expandedCategories.has(
                                                    group.category,
                                                )
                                            "
                                            class="d-rule-b px-5 pb-4"
                                        >
                                            <div
                                                class="flex flex-wrap gap-1.5 pt-3"
                                            >
                                                <span
                                                    v-for="skill in group.skills"
                                                    :key="skill.id"
                                                    class="inline-flex items-center bg-(--accent-soft) px-2.5 py-1 text-xs font-medium text-(--ink)"
                                                >
                                                    {{ skill.name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- ═══════════════════ EXPERIENCE ═══════════════════ -->
                        <section
                            v-if="sections.experiences.length"
                            id="experience"
                            aria-labelledby="experience-title"
                            class="d-rule scroll-mt-20"
                        >
                            <div
                                class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24"
                            >
                                <div
                                    class="mb-10 flex flex-wrap items-end justify-between gap-4"
                                    data-motion
                                >
                                    <div>
                                        <p class="d-section mb-2">Experience</p>
                                        <h2
                                            id="experience-title"
                                            class="font-display text-3xl font-bold tracking-tight sm:text-4xl"
                                        >
                                            Where I've been
                                        </h2>
                                    </div>
                                </div>

                                <!-- Timeline layout -->
                                <div class="relative" data-motion-group>
                                    <!-- Vertical line -->
                                    <div
                                        class="absolute top-0 bottom-0 left-[19px] w-px bg-(--rule) sm:left-[23px]"
                                        aria-hidden="true"
                                    />

                                    <ol class="space-y-8">
                                        <li
                                            v-for="exp in sections.experiences"
                                            :key="exp.id"
                                            class="relative pl-14 sm:pl-16"
                                            data-motion
                                        >
                                            <!-- Timeline dot -->
                                            <span
                                                class="absolute top-1 left-3 flex size-[14px] items-center justify-center sm:left-[16px]"
                                                aria-hidden="true"
                                            >
                                                <span
                                                    class="size-2.5 rounded-full bg-(--accent)"
                                                />
                                                <span
                                                    class="absolute size-[14px] rounded-full bg-(--accent) opacity-20"
                                                />
                                            </span>

                                            <article
                                                class="d-surface p-5 sm:p-6"
                                            >
                                                <div
                                                    class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between"
                                                >
                                                    <h3
                                                        class="font-display text-lg font-bold tracking-tight"
                                                    >
                                                        {{ exp.role }}
                                                    </h3>
                                                    <time
                                                        class="d-label shrink-0"
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
                                                    class="mt-1 text-sm font-medium text-(--accent)"
                                                >
                                                    {{ exp.company }}
                                                    <span
                                                        v-if="exp.location"
                                                        class="font-normal text-(--ink-soft)"
                                                    >
                                                        · {{ exp.location }}
                                                    </span>
                                                </p>
                                                <p
                                                    class="mt-3 max-w-2xl leading-relaxed text-pretty"
                                                >
                                                    {{ exp.summary }}
                                                </p>
                                                <ul
                                                    v-if="exp.highlights.length"
                                                    class="mt-3 grid max-w-2xl gap-1.5"
                                                >
                                                    <li
                                                        v-for="highlight in exp.highlights"
                                                        :key="highlight"
                                                        class="flex items-start gap-2 text-sm text-(--ink-soft)"
                                                    >
                                                        <span
                                                            class="mt-1.5 size-1.5 shrink-0 rounded-full bg-(--accent)"
                                                            aria-hidden="true"
                                                        />
                                                        {{ highlight }}
                                                    </li>
                                                </ul>
                                            </article>
                                        </li>
                                    </ol>
                                </div>
                            </div>
                        </section>

                        <!-- ═══════════════════ PROJECTS ═══════════════════ -->
                        <section
                            id="projects"
                            aria-labelledby="projects-title"
                            class="d-rule scroll-mt-20"
                        >
                            <div
                                class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24"
                            >
                                <div
                                    class="mb-10 flex flex-wrap items-end justify-between gap-4"
                                    data-motion
                                >
                                    <div>
                                        <p class="d-section mb-2">Projects</p>
                                        <h2
                                            id="projects-title"
                                            class="font-display text-3xl font-bold tracking-tight sm:text-4xl"
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
                                        <ExternalLink
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                        <span class="sr-only"
                                            >(opens in a new tab)</span
                                        >
                                    </a>
                                </div>

                                <!-- Asymmetric bento grid -->
                                <div
                                    class="grid gap-4 lg:grid-cols-12"
                                    data-motion-group
                                >
                                    <article
                                        v-for="(
                                            project, i
                                        ) in sections.projects"
                                        :key="project.id"
                                        class="d-surface flex flex-col overflow-hidden"
                                        :class="
                                            i % 3 === 0
                                                ? 'lg:col-span-7'
                                                : i % 3 === 1
                                                  ? 'lg:col-span-5'
                                                  : 'lg:col-span-12'
                                        "
                                        data-motion
                                    >
                                        <img
                                            v-if="project.screenshots[0]?.url"
                                            :src="project.screenshots[0].url"
                                            :alt="
                                                project.screenshots[0].alt ??
                                                project.title
                                            "
                                            loading="lazy"
                                            decoding="async"
                                            class="aspect-[16/9] w-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="relative flex aspect-[16/9] w-full items-center justify-center bg-(--accent-soft)"
                                            aria-hidden="true"
                                        >
                                            <span class="d-label">{{
                                                project.year
                                            }}</span>
                                        </div>
                                        <div
                                            class="flex flex-1 flex-col gap-3 p-5 sm:p-6"
                                        >
                                            <div
                                                class="flex items-baseline justify-between gap-2"
                                            >
                                                <h3
                                                    class="font-display text-lg font-bold tracking-tight"
                                                >
                                                    {{ project.title }}
                                                </h3>
                                                <span class="d-label">{{
                                                    project.year
                                                }}</span>
                                            </div>
                                            <p
                                                class="max-w-prose text-sm leading-relaxed text-pretty"
                                            >
                                                {{ project.description }}
                                            </p>
                                            <ul
                                                class="mt-auto flex flex-wrap gap-1.5 pt-2"
                                            >
                                                <li
                                                    v-for="skill in project.skills.slice(
                                                        0,
                                                        5,
                                                    )"
                                                    :key="skill.id"
                                                    class="inline-flex items-center bg-(--accent-soft) px-2 py-0.5 text-[11px] font-medium"
                                                >
                                                    {{ skill.name }}
                                                </li>
                                                <li
                                                    v-if="
                                                        project.skills.length >
                                                        5
                                                    "
                                                    class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium text-(--ink-soft)"
                                                >
                                                    +{{
                                                        project.skills.length -
                                                        5
                                                    }}
                                                </li>
                                            </ul>
                                            <div
                                                class="mt-2 flex gap-2 border-t border-(--rule) pt-3"
                                            >
                                                <a
                                                    v-if="project.live_url"
                                                    :href="project.live_url"
                                                    target="_blank"
                                                    rel="noreferrer noopener"
                                                    class="d-press inline-flex min-h-9 items-center gap-1.5 bg-(--accent) px-4 py-1.5 text-xs font-semibold text-(--paper) no-underline transition-colors hover:bg-(--accent-hover)"
                                                >
                                                    Live site
                                                    <ArrowUpRight
                                                        class="size-3.5"
                                                        aria-hidden="true"
                                                    />
                                                    <span class="sr-only"
                                                        >(opens in a new
                                                        tab)</span
                                                    >
                                                </a>
                                                <a
                                                    v-if="project.repo_url"
                                                    :href="project.repo_url"
                                                    target="_blank"
                                                    rel="noreferrer noopener"
                                                    class="d-press d-hover inline-flex min-h-9 items-center gap-1.5 px-4 py-1.5 text-xs font-semibold no-underline"
                                                >
                                                    Source
                                                    <ExternalLink
                                                        class="size-3.5"
                                                        aria-hidden="true"
                                                    />
                                                    <span class="sr-only"
                                                        >(opens in a new
                                                        tab)</span
                                                    >
                                                </a>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            </div>
                        </section>

                        <!-- ═══════════════════ WRITING ═══════════════════ -->
                        <section
                            v-if="sections.posts.length"
                            id="writing"
                            aria-labelledby="writing-title"
                            class="d-rule scroll-mt-20"
                        >
                            <div
                                class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24"
                            >
                                <div
                                    class="mb-10 flex flex-wrap items-end justify-between gap-4"
                                    data-motion
                                >
                                    <div>
                                        <p class="d-section mb-2">Writing</p>
                                        <h2
                                            id="writing-title"
                                            class="font-display text-3xl font-bold tracking-tight sm:text-4xl"
                                        >
                                            Recent notes
                                        </h2>
                                    </div>
                                    <Link
                                        :href="blogIndex()"
                                        prefetch
                                        cache-for="10s"
                                        class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold no-underline"
                                    >
                                        All posts
                                        <ArrowUpRight
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                    </Link>
                                </div>

                                <div class="space-y-3" data-motion-group>
                                    <Link
                                        v-for="post in sections.posts"
                                        :key="post.id"
                                        :href="
                                            postShow.url({ post: post.slug })
                                        "
                                        prefetch
                                        cache-for="10s"
                                        class="d-surface d-card-hover block p-5 no-underline sm:p-6"
                                        data-motion
                                    >
                                        <div
                                            class="flex flex-col gap-2 sm:flex-row sm:items-baseline sm:justify-between"
                                        >
                                            <h3
                                                class="font-display text-base font-semibold"
                                            >
                                                {{ post.title }}
                                            </h3>
                                            <time class="d-label shrink-0">
                                                {{
                                                    new Date(
                                                        post.published_at,
                                                    ).toLocaleDateString(
                                                        'en-US',
                                                        {
                                                            month: 'short',
                                                            day: 'numeric',
                                                            year: 'numeric',
                                                        },
                                                    )
                                                }}
                                            </time>
                                        </div>
                                        <p
                                            class="mt-2 line-clamp-2 text-sm leading-relaxed text-(--ink-soft)"
                                        >
                                            {{
                                                post.teaser_text
                                            }}
                                        </p>
                                    </Link>
                                </div>
                            </div>
                        </section>

                        <!-- ═══════════════════ EDUCATION ═══════════════════ -->
                        <section
                            v-if="
                                sections.educations.length ||
                                sections.publications.length
                            "
                            id="education"
                            aria-labelledby="education-title"
                            class="d-rule scroll-mt-20"
                        >
                            <div
                                class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24"
                            >
                                <div class="mb-10 max-w-2xl" data-motion>
                                    <p class="d-section mb-2">Education</p>
                                    <h2
                                        id="education-title"
                                        class="font-display text-3xl font-bold tracking-tight sm:text-4xl"
                                    >
                                        Academic grounding
                                    </h2>
                                </div>

                                <div
                                    class="grid gap-4 lg:grid-cols-2"
                                    data-motion-group
                                >
                                    <article
                                        v-for="edu in sections.educations"
                                        :key="edu.id"
                                        class="d-surface flex flex-col gap-3 p-5 sm:p-6"
                                        data-motion
                                    >
                                        <div class="flex items-start gap-3">
                                            <span
                                                class="flex size-9 shrink-0 items-center justify-center bg-(--accent-soft)"
                                                aria-hidden="true"
                                            >
                                                <GraduationCap
                                                    class="size-4 text-(--accent)"
                                                />
                                            </span>
                                            <div>
                                                <h3
                                                    class="font-display text-base font-bold tracking-tight"
                                                >
                                                    {{ edu.school }}
                                                </h3>
                                                <p
                                                    class="text-sm font-medium text-(--accent)"
                                                >
                                                    {{ edu.degree }}
                                                </p>
                                                <time class="d-label mt-1">
                                                    {{
                                                        edu.started_at
                                                            ? formatDateRange(
                                                                  edu.started_at,
                                                                  edu.ended_at,
                                                              )
                                                            : edu.ended_at
                                                              ? new Date(
                                                                    edu.ended_at,
                                                                ).toLocaleDateString(
                                                                    'en-US',
                                                                    {
                                                                        month: 'short',
                                                                        year: 'numeric',
                                                                    },
                                                                )
                                                              : 'Present'
                                                    }}
                                                </time>
                                            </div>
                                        </div>
                                        <ul
                                            v-if="edu.details.length"
                                            class="mt-1 grid gap-1.5"
                                        >
                                            <li
                                                v-for="detail in edu.details"
                                                :key="detail"
                                                class="flex items-start gap-2 text-sm text-(--ink-soft)"
                                            >
                                                <span
                                                    class="mt-1.5 size-1.5 shrink-0 rounded-full bg-(--accent)"
                                                    aria-hidden="true"
                                                />
                                                {{ detail }}
                                            </li>
                                        </ul>
                                    </article>
                                </div>

                                <div
                                    v-if="sections.publications.length"
                                    class="mt-8 space-y-3"
                                    data-motion-group
                                >
                                    <article
                                        v-for="pub in sections.publications"
                                        :key="pub.id"
                                        class="d-surface p-5"
                                        data-motion
                                    >
                                        <p class="d-label">
                                            Publication · {{ pub.year }} ·
                                            {{ pub.venue }}
                                        </p>
                                        <p
                                            class="mt-2 max-w-3xl leading-relaxed text-pretty"
                                        >
                                            {{ pub.citation }}
                                        </p>
                                        <a
                                            :href="pub.doi_url"
                                            target="_blank"
                                            rel="noreferrer noopener"
                                            class="mt-2 inline-flex min-h-9 items-center gap-1.5 text-sm font-semibold no-underline"
                                        >
                                            Read the paper (DOI)
                                            <ExternalLink
                                                class="size-3.5"
                                                aria-hidden="true"
                                            />
                                            <span class="sr-only"
                                                >(opens in a new tab)</span
                                            >
                                        </a>
                                    </article>
                                </div>
                            </div>
                        </section>
                    </div>
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
