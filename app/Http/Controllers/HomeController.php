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
        $profile = Profile::current();
        $profile->bio_html = $profile->bioHtml();

        return Inertia::render('Welcome', [
            'profile' => $profile,
            'stats' => [
                'years_active' => $this->yearsActive(),
                'projects_count' => Project::query()->published()->count(),
                'skills_count' => Skill::query()->count(),
            ],
            'turnstile_site_key' => config('contact.turnstile_site_key'),
            'skills' => Inertia::defer(fn () => Skill::query()
                ->orderBy('category')
                ->orderBy('name')
                ->get())->once(),
            'experiences' => Inertia::defer(fn () => Experience::query()
                ->orderByDesc('started_at')
                ->get())->once(),
            'projects' => Inertia::defer(fn () => Project::query()
                ->published()
                ->with(['skills', 'screenshots'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get())->once(),
            'educations' => Inertia::defer(fn () => Education::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get())->once(),
            'publications' => Inertia::defer(fn () => Publication::query()
                ->orderByDesc('year')
                ->orderBy('id')
                ->get())->once(),
            'posts' => Inertia::defer(fn () => Post::query()
                ->published()
                ->orderByDesc('published_at')
                ->limit(3)
                ->get()
                ->each(fn (Post $post) => $post->teaser_text = $post->teaser()))->once(),
        ]);
    }

    /**
     * Years spanned by the experience timeline, matching the hero stat.
     */
    private function yearsActive(): int
    {
        $experiences = Experience::query()->get(['started_at', 'ended_at']);

        if ($experiences->isEmpty()) {
            return 0;
        }

        $starts = $experiences->pluck('started_at')
            ->map(fn (string $date) => strtotime($date));
        $ends = $experiences->pluck('ended_at')
            ->map(fn (?string $date) => $date === null ? time() : strtotime($date));

        $months = ($ends->max() - $starts->min()) / (1000 * 60 * 60 * 24 * 30.44);

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

        foreach (Post::published()->orderByDesc('published_at')->get() as $post) {
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
