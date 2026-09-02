<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    BookOpen,
    ChevronDown,
    Code2,
    Database,
    ExternalLink,
    GraduationCap,
    Layers,
    Rocket,
    Shield,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import ContentCard from '@/components/site/ContentCard.vue';
import type {
    Education,
    Experience,
    Profile,
    Project,
    Publication,
    PublicPost,
    Skill,
    SkillCategory,
} from '@/data/portfolio';
import { formatDateRange } from '@/data/portfolio';
import { formatDate } from '@/lib/utils';
import { index as blogIndex, show as postShow } from '@/routes/posts';

/**
 * Below-the-fold homepage sections (skills, experience, projects, writing,
 * education). Own chunk, loaded by Welcome.vue after the hero paints and
 * mounted only once the deferred Inertia props land. Emits `contentMounted`
 * once its DOM is committed so Welcome can (re)scan the scroll-reveal
 * system over the freshly mounted sections.
 */

const props = defineProps<{
    profile: Profile;
    reloading?: boolean;
    skills?: Skill[];
    experiences?: Experience[];
    projects?: Project[];
    educations?: Education[];
    publications?: Publication[];
    posts?: PublicPost[];
}>();

const emit = defineEmits<{ contentMounted: [] }>();

onMounted(() => {
    emit('contentMounted');
});

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
</script>

<template>
    <div :class="{ 'opacity-60 transition-opacity': reloading }">
        <!-- ═══════════════════ SKILLS (UX: grouped, expandable) ═══════════════════ -->
        <section
            v-if="sections.skills.length"
            id="skills"
            aria-labelledby="skills-title"
            class="d-rule scroll-mt-20"
        >
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
                <div class="mb-10 max-w-2xl" data-motion>
                    <p class="d-section mb-2">Skills</p>
                    <h2
                        id="skills-title"
                        class="font-display text-3xl font-bold tracking-tight sm:text-4xl"
                    >
                        What I work with
                    </h2>
                    <p class="mt-3 text-base leading-relaxed text-(--ink-soft)">
                        Grouped so you don't have to scroll through a wall of
                        text. Tap a category to see what's inside.
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
                                expandedCategories.has(group.category)
                            "
                            @click="toggleCategory(group.category)"
                        >
                            <span
                                class="flex size-10 shrink-0 items-center justify-center bg-(--accent-soft)"
                                aria-hidden="true"
                            >
                                <component
                                    :is="categoryMeta[group.category].icon"
                                    class="size-5 text-(--accent)"
                                />
                            </span>
                            <div class="flex-1">
                                <p class="font-display text-base font-semibold">
                                    {{ categoryMeta[group.category].label }}
                                </p>
                                <p class="d-label mt-0.5">
                                    {{ group.skills.length }}
                                    {{
                                        group.skills.length === 1
                                            ? 'tool'
                                            : 'tools'
                                    }}
                                </p>
                            </div>
                            <ChevronDown
                                class="size-4 shrink-0 text-(--ink-soft) transition-transform"
                                :class="{
                                    'rotate-180': expandedCategories.has(
                                        group.category,
                                    ),
                                }"
                                aria-hidden="true"
                            />
                        </button>
                        <div
                            v-show="expandedCategories.has(group.category)"
                            class="d-rule-b px-5 pb-4"
                        >
                            <div class="flex flex-wrap gap-1.5 pt-3">
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
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
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

                            <article class="d-surface p-5 sm:p-6">
                                <div
                                    class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between"
                                >
                                    <h3
                                        class="font-display text-lg font-bold tracking-tight"
                                    >
                                        {{ exp.role }}
                                    </h3>
                                    <time class="d-label shrink-0">
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
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
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
                        <ExternalLink class="size-4" aria-hidden="true" />
                        <span class="sr-only">(opens in a new tab)</span>
                    </a>
                </div>

                <!-- Asymmetric bento grid -->
                <div class="grid gap-4 lg:grid-cols-12" data-motion-group>
                    <article
                        v-for="(project, i) in sections.projects"
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
                            :alt="project.screenshots[0].alt ?? project.title"
                            loading="lazy"
                            decoding="async"
                            class="aspect-[16/9] w-full object-cover"
                        />
                        <div
                            v-else
                            class="relative flex aspect-[16/9] w-full items-center justify-center bg-(--accent-soft)"
                            aria-hidden="true"
                        >
                            <span class="d-label">{{ project.year }}</span>
                        </div>
                        <div class="flex flex-1 flex-col gap-3 p-5 sm:p-6">
                            <div
                                class="flex items-baseline justify-between gap-2"
                            >
                                <h3
                                    class="font-display text-lg font-bold tracking-tight"
                                >
                                    {{ project.title }}
                                </h3>
                                <span class="d-label">{{ project.year }}</span>
                            </div>
                            <p
                                class="max-w-prose text-sm leading-relaxed text-pretty"
                            >
                                {{ project.description }}
                            </p>
                            <ul class="mt-auto flex flex-wrap gap-1.5 pt-2">
                                <li
                                    v-for="skill in project.skills.slice(0, 5)"
                                    :key="skill.id"
                                    class="inline-flex items-center bg-(--accent-soft) px-2 py-0.5 text-[11px] font-medium"
                                >
                                    {{ skill.name }}
                                </li>
                                <li
                                    v-if="project.skills.length > 5"
                                    class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium text-(--ink-soft)"
                                >
                                    +{{ project.skills.length - 5 }}
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
                                        >(opens in a new tab)</span
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
                                        >(opens in a new tab)</span
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
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
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
                        <ArrowUpRight class="size-4" aria-hidden="true" />
                    </Link>
                </div>

                <div class="space-y-3" data-motion-group>
                    <ContentCard
                        v-for="post in sections.posts"
                        :key="post.id"
                        :href="postShow.url({ post: post.slug })"
                        :title="post.title"
                        :date="formatDate(post.published_at)"
                        :description="post.teaser_text"
                        title-tag="h3"
                        title-class="text-base"
                        compact
                        description-class="mt-2 line-clamp-2 text-sm leading-relaxed text-(--ink-soft)"
                    />
                </div>
            </div>
        </section>

        <!-- ═══════════════════ EDUCATION ═══════════════════ -->
        <section
            v-if="sections.educations.length || sections.publications.length"
            id="education"
            aria-labelledby="education-title"
            class="d-rule scroll-mt-20"
        >
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
                <div class="mb-10 max-w-2xl" data-motion>
                    <p class="d-section mb-2">Education</p>
                    <h2
                        id="education-title"
                        class="font-display text-3xl font-bold tracking-tight sm:text-4xl"
                    >
                        Academic grounding
                    </h2>
                </div>

                <div class="grid gap-4 lg:grid-cols-2" data-motion-group>
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
                                <GraduationCap class="size-4 text-(--accent)" />
                            </span>
                            <div>
                                <h3
                                    class="font-display text-base font-bold tracking-tight"
                                >
                                    {{ edu.school }}
                                </h3>
                                <p class="text-sm font-medium text-(--accent)">
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
                                                ).toLocaleDateString('en-US', {
                                                    month: 'short',
                                                    year: 'numeric',
                                                })
                                              : 'Present'
                                    }}
                                </time>
                            </div>
                        </div>
                        <ul v-if="edu.details.length" class="mt-1 grid gap-1.5">
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
                        <p class="mt-2 max-w-3xl leading-relaxed text-pretty">
                            {{ pub.citation }}
                        </p>
                        <a
                            :href="pub.doi_url"
                            target="_blank"
                            rel="noreferrer noopener"
                            class="mt-2 inline-flex min-h-9 items-center gap-1.5 text-sm font-semibold no-underline"
                        >
                            Read the paper (DOI)
                            <ExternalLink class="size-3.5" aria-hidden="true" />
                            <span class="sr-only">(opens in a new tab)</span>
                        </a>
                    </article>
                </div>
            </div>
        </section>
    </div>
</template>
