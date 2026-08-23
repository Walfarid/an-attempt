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
    category: SkillCategory;
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

export type ProjectScreenshot = {
    id: number;
    project_id: number;
    path: string;
    alt: string | null;
    sort_order: number;
    url: string | null;
};

export type Project = {
    id: number;
    slug: string;
    title: string;
    description: string;
    year: number;
    live_url: string | null;
    repo_url: string | null;
    image_tone: string | null;
    featured: boolean;
    sort_order: number;
    published_at: string | null;
    skills: Skill[];
    screenshots: ProjectScreenshot[];
};

export type Education = {
    id: number;
    school: string;
    degree: string;
    started_at: string | null;
    ended_at: string | null; // null = present
    details: string[];
    sort_order: number;
};

export type Publication = {
    id: number;
    citation: string;
    venue: string;
    year: number;
    doi_url: string;
    sort_order: number;
};

export type Profile = {
    id: number;
    name: string;
    headline: string;
    bio: string;
    bio_html?: string;
    location: string | null;
    github_url: string | null;
    linkedin_url: string | null;
    avatar_path: string | null;
};

/** A post as listed on the dashboard (full fields). */
export type Post = {
    id: number;
    slug: string;
    title: string;
    excerpt: string | null;
    body: string;
    cover_image_path: string | null;
    cover_url: string | null;
    published_at: string | null;
};

/** A post teaser on a public page (no body). */
export type PublicPost = {
    id: number;
    slug: string;
    title: string;
    excerpt: string | null;
    cover_url: string | null;
    published_at: string;
    teaser_text?: string;
};

/** A rendered post page. */
export type PublicPostDetail = {
    id: number;
    slug: string;
    title: string;
    excerpt: string | null;
    cover_url: string | null;
    published_at: string;
    body_html: string;
};

export function formatDateRange(start: string, end: string | null): string {
    const fmt = (iso: string) =>
        new Date(iso).toLocaleDateString('en-US', {
            month: 'short',
            year: 'numeric',
        });

    return `${fmt(start)} · ${end ? fmt(end) : 'Present'}`;
}
