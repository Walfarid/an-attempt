<?php

namespace App\Http\Controllers;

use App\Models\Education;
use App\Models\Experience;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Publication;
use App\Models\Skill;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Laravel\Head\Facades\Head;
use Laravel\Head\Facades\Schema;

class HomeController extends Controller
{
    /**
     * The public portfolio homepage.
     *
     * The hero, stats, and contact form ship with the initial page; the
     * below-the-fold sections are deferred so the first paint stays fast.
     * `once()` keeps them cached client-side for back/forward visits.
     */
    public function index(): InertiaResponse
    {
        // One query instead of two: the hero-stats aggregate (years active,
        // project count, skill count) is inlined into the profile SELECT as
        // scalar subqueries — the same pattern stats() used on its own.
        $profile = Profile::query()
            ->select([
                'id', 'name', 'headline', 'bio', 'location', 'github_url', 'linkedin_url',
            ])
            ->selectRaw('
                (SELECT MIN(started_at) FROM experiences) as years_earliest,
                (SELECT MAX(COALESCE(ended_at, CURRENT_TIMESTAMP)) FROM experiences) as years_latest,
                (SELECT COUNT(*) FROM projects WHERE published_at IS NOT NULL AND published_at <= CURRENT_TIMESTAMP) as projects_count,
                (SELECT COUNT(*) FROM skills) as skills_count
            ')
            ->firstOrFail();

        $profile->bio_html = $profile->bioHtml();
        $profile->makeHidden([
            'bio', 'years_earliest', 'years_latest', 'projects_count', 'skills_count',
        ]);

        Head::title('Home')
            ->description($profile->headline.' — '.'Software developer with over 6 years of experience in application development, API management, and deployment platforms.')
            ->canonical();

        Head::schema(
            Schema::person()
                ->name($profile->name)
                ->url(url('/'))
                ->set('jobTitle', $profile->headline)
                ->set('description', strip_tags($profile->bio_html))
                ->set('sameAs', array_filter([
                    $profile->github_url,
                    $profile->linkedin_url,
                ]))
        );

        return Inertia::render('Welcome', [
            'profile' => $profile,
            'stats' => $this->stats($profile),
            'turnstile_site_key' => config('contact.turnstile_site_key'),
            'skills' => Inertia::defer(fn () => Skill::query()
                ->select(['id', 'name', 'category'])
                ->orderBy('category')
                ->orderBy('name')
                ->get())->once(),
            'experiences' => Inertia::defer(fn () => Experience::query()
                ->select(['id', 'role', 'company', 'location', 'started_at', 'ended_at', 'summary', 'highlights'])
                ->orderByDesc('started_at')
                ->get())->once(),
            'projects' => Inertia::defer(fn () => Project::query()
                ->select(['id', 'title', 'description', 'year', 'live_url', 'repo_url'])
                ->published()
                ->with(['skills' => fn ($q) => $q->select(['skills.id', 'skills.name']), 'screenshots' => fn ($q) => $q->select(['id', 'project_id', 'path', 'alt'])])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->each(function (Project $project): void {
                    $project->screenshots->each->makeHidden([
                        'project_id', 'path', 'sort_order', 'created_at', 'updated_at',
                    ]);
                    // BelongsToMany always carries pivot columns (project_id,
                    // skill_id) in serialized pivot payloads — ~37 B/row of
                    // dead wire. The frontend renders skill.name (id as key),
                    // so rebuild the relation as plain {id, name} arrays.
                    $project->setRelation('skills', $project->skills->map->only(['id', 'name']));
                }))->once(),
            'educations' => Inertia::defer(fn () => Education::query()
                ->select(['id', 'school', 'degree', 'started_at', 'ended_at', 'details'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get())->once(),
            'publications' => Inertia::defer(fn () => Publication::query()
                ->select(['id', 'citation', 'venue', 'year', 'doi_url'])
                ->orderByDesc('year')
                ->orderBy('id')
                ->get())->once(),
            'posts' => Inertia::defer(fn () => Post::query()
                ->select(['id', 'slug', 'title', 'excerpt', 'published_at'])
                ->selectRaw('SUBSTRING(body, 1, 300) as body_preview')
                ->published()
                ->orderByDesc('published_at')
                ->limit(3)
                ->get()
                ->each(function (Post $post): void {
                    $post->teaser_text = $post->teaser();
                    // excerpt/body_preview are only inputs to teaser(); the
                    // frontend reads teaser_text, so keep them off the wire.
                    $post->makeHidden(['excerpt', 'body_preview']);
                }))->once(),
        ]);
    }

    /**
     * Hero stats derived from the aggregate columns inlined into the
     * profile query: years active, published project count, total skill count.
     *
     * @param  Profile  $profile  Profile carrying the years_earliest/years_latest/projects_count/skills_count attributes
     * @return array{years_active: int, projects_count: int, skills_count: int}
     */
    private function stats(Profile $profile): array
    {
        $yearsActive = 0;

        if ($profile->years_earliest !== null) {
            $months = (strtotime($profile->years_latest) - strtotime($profile->years_earliest)) / (86400 * 30.44);
            $yearsActive = max(1, (int) round($months / 12));
        }

        return [
            'years_active' => $yearsActive,
            'projects_count' => (int) $profile->projects_count,
            'skills_count' => (int) $profile->skills_count,
        ];
    }

    /**
     * Generate a dynamic XML sitemap of public pages.
     */
    public function sitemap(): Response
    {
        $posts = Post::published()->select(['slug', 'updated_at'])->orderByDesc('published_at')->get();

        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => url('/posts'), 'priority' => '0.8'],
        ];

        foreach ($posts as $post) {
            $urls[] = [
                'loc' => route('posts.show', $post->slug),
                'lastmod' => $post->updated_at->toW3cString(),
                'priority' => '0.6',
            ];
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        // Compute Last-Modified from the already-fetched posts (no extra query).
        $lastModified = $posts->max('updated_at') ?: now();

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age=3600',
            'Last-Modified' => $lastModified->toRfc7231String(),
        ]);
    }
}
