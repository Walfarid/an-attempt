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
use Illuminate\Support\Facades\DB;
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
        $profile = Profile::query()->firstOrFail([
            'id', 'name', 'headline', 'bio', 'location', 'github_url', 'linkedin_url',
        ]);
        $profile->bio_html = $profile->bioHtml();
        $profile->makeHidden(['bio']);

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
            'stats' => $this->stats(),
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
                ->with(['skills' => fn ($q) => $q->select(['skills.id', 'skills.name', 'skills.category']), 'screenshots' => fn ($q) => $q->select(['id', 'project_id', 'path', 'alt'])])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->each(function (Project $project): void {
                    $project->screenshots->each->makeHidden([
                        'project_id', 'path', 'sort_order', 'created_at', 'updated_at',
                    ]);
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
                }))->once(),
        ]);
    }

    /**
     * Hero stats in a single aggregate query: years active, published
     * project count, and total skill count.
     *
     * @return array{years_active: int, projects_count: int, skills_count: int}
     */
    private function stats(): array
    {
        $row = DB::table('experiences')
            ->selectRaw('
                MIN(started_at) as earliest,
                MAX(COALESCE(ended_at, CURRENT_TIMESTAMP)) as latest,
                (SELECT COUNT(*) FROM projects WHERE published_at IS NOT NULL AND published_at <= CURRENT_TIMESTAMP) as projects_count,
                (SELECT COUNT(*) FROM skills) as skills_count
            ')
            ->first();

        $yearsActive = 0;

        if ($row !== null && $row->earliest !== null) {
            $months = (strtotime($row->latest) - strtotime($row->earliest)) / (86400 * 30.44);
            $yearsActive = max(1, (int) round($months / 12));
        }

        return [
            'years_active' => $yearsActive,
            'projects_count' => (int) ($row->projects_count ?? 0),
            'skills_count' => (int) ($row->skills_count ?? 0),
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
