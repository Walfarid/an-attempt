/**
 * Portfolio entity types + formatting helpers.
 *
 * The backend tables mirror these shapes (see `.ai/rules/content-model.md`);
 * props come straight from Eloquent serialization via Inertia.
 */

export type SkillCategory =
    | 'languages'
    | 'frameworks'
    | 'databases'
    | 'devops'
    | 'platform'
    | 'security';

export type Skill = {
    id: number;
    name: string;
    category?: SkillCategory;
};

export type Experience = {
    id: number;
    role: string;
    company: string;
    location: string | null;
    started_at: string; // ISO datetime
    ended_at: string | null; // null = present
    summary: string;
    highlights: string[];
};

/** Public shape — the dashboard re-adds `id` (needed for screenshot deletion). */
export type ProjectScreenshot = {
    alt: string | null;
    url: string | null;
};

export type Project = {
    id: number;
    title: string;
    description: string;
    year: number;
    live_url: string | null;
    repo_url: string | null;
    skills: Skill[];
    screenshots: ProjectScreenshot[];
    /** Present on dashboard responses, absent on public homepage. */
    slug?: string;
    image_tone?: string | null;
    featured?: boolean;
    sort_order?: number;
    published_at?: string | null;
};

export type Education = {
    id: number;
    school: string;
    degree: string;
    started_at: string | null;
    ended_at: string | null;
    details: string[];
};

export type Publication = {
    id: number;
    citation: string;
    venue: string;
    year: number;
    doi_url: string;
};

/** A dashboard upload: image stored on the media disk, referenced by markdown. */
export type Media = {
    id: number;
    name: string;
    url: string | null;
    mime: string;
    size: number; // bytes
    created_at: string | null;
};

export type Profile = {
    name: string;
    headline: string;
    bio_html?: string;
    location: string | null;
    github_url: string | null;
    linkedin_url: string | null;
    /** Present on dashboard responses, absent on public homepage. */
    bio?: string;
    avatar_path?: string | null;
};

/** A post as listed on the dashboard (body loaded lazily via show endpoint). */
export type Post = {
    id: number;
    slug: string;
    title: string;
    excerpt?: string | null;
    body?: string;
    cover_url: string | null;
    published_at: string | null;
    /** Present on the dashboard show endpoint (editor consumes it). */
    tags?: string[];
};

/** A post teaser on a public page (no body). */
export type PublicPost = {
    id: number;
    slug: string;
    title: string;
    published_at: string;
    teaser_text?: string;
    cover_url?: string | null;
    tags?: PublicTag[];
};

/** A public tag reference (pills on cards/pages). */
export type PublicTag = {
    id: number;
    slug: string;
    name: string;
};

/** A rendered post page. */
export type PublicPostDetail = {
    title: string;
    cover_url: string | null;
    published_at: string;
    body_html: string;
    tags?: PublicTag[];
};

// Guide (dashboard editor) -------------------------------------------

/** A full guide as loaded by the dashboard editor (body + linked post IDs). */
export interface Guide {
    id: number;
    slug: string;
    title: string;
    body?: string;
    teaser: string | null;
    prerequisites: string | null;
    estimated_time: string | null;
    cover_url: string | null;
    published_at: string | null;
    /** Post IDs for the linking UI. Present on the dashboard show endpoint. */
    posts?: number[];
}

/** A guide row on the dashboard table (no body). */
export type GuideListItem = {
    id: number;
    slug: string;
    title: string;
    cover_url: string | null;
    estimated_time: string | null;
    published_at: string | null;
};

/** A post option in the dashboard related-posts picker. */
export type PostOption = {
    id: number;
    title: string;
};

// Guide (public) ------------------------------------------------------

/** A guide teaser on a public index page (no body). */
export interface PublicGuide {
    slug: string;
    title: string;
    teaser: string | null;
    estimated_time: string | null;
    cover_url: string | null;
    published_at: string;
}

/** A rendered (or rendering) guide page. */
export interface PublicGuideDetail {
    title: string;
    slug: string;
    body_html: string;
    teaser: string | null;
    prerequisites: string | null;
    estimated_time: string | null;
    cover_url: string | null;
    published_at: string;
    posts: { id: number; slug: string; title: string; published_at: string }[];
}

export function formatDateRange(start: string, end: string | null): string {
    const fmt = (iso: string) =>
        new Date(iso).toLocaleDateString('en-US', {
            month: 'short',
            year: 'numeric',
        });

    return `${fmt(start)} · ${end ? fmt(end) : 'Present'}`;
}
