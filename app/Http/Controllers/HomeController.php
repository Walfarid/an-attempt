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
            'stats' => [
                'years_active' => $this->yearsActive(),
                'projects_count' => Project::query()->published()->count(),
                'skills_count' => Skill::query()->count(),
            ],
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
                ->with(['skills', 'screenshots'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->each(function (Project $project): void {
                    $project->screenshots->each->makeHidden(['project_id', 'path', 'sort_order', 'created_at', 'updated_at']);
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
                ->select(['id', 'slug', 'title', 'excerpt', 'body', 'cover_image_path', 'published_at'])
                ->published()
                ->orderByDesc('published_at')
                ->limit(3)
                ->get()
                ->each(function (Post $post): void {
                    $post->teaser_text = $post->teaser();
                    $post->makeHidden(['body', 'cover_image_path', 'created_at', 'updated_at']);
                }))->once(),
        ]);
    }

    /**
     * Years spanned by the experience timeline, matching the hero stat.
     */
    private function yearsActive(): int
    {
        $row = DB::table('experiences')
            ->selectRaw('MIN(started_at) as earliest, MAX(COALESCE(ended_at, CURRENT_TIMESTAMP)) as latest')
            ->first();

        if ($row === null || $row->earliest === null) {
            return 0;
        }

        $months = (strtotime($row->latest) - strtotime($row->earliest)) / (86400 * 30.44);

        return max(1, (int) round($months / 12));
    }

    /**
     * Generate a dynamic XML sitemap of public pages.
     */
    public function sitemap(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => url('/posts'), 'priority' => '0.8'],
        ];

        foreach (Post::published()->select(['slug', 'updated_at'])->orderByDesc('published_at')->get() as $post) {
            $urls[] = [
                'loc' => route('posts.show', $post->slug),
                'lastmod' => $post->updated_at->toW3cString(),
                'priority' => '0.6',
            ];
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
