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
     */
    public function index(): InertiaResponse
    {
        $profile = Profile::current();
        $profile->bio_html = $profile->bioHtml();

        return Inertia::render('Welcome', [
            'profile' => $profile,
            'experiences' => Experience::query()
                ->orderByDesc('started_at')
                ->get(),
            'skills' => Skill::query()
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
            'projects' => Project::query()
                ->published()
                ->with(['skills', 'screenshots'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'educations' => Education::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'publications' => Publication::query()
                ->orderByDesc('year')
                ->orderBy('id')
                ->get(),
            'posts' => Post::query()
                ->published()
                ->orderByDesc('published_at')
                ->limit(3)
                ->get()
                ->each(fn (Post $post) => $post->teaser_text = $post->teaser()),
            'turnstile_site_key' => config('contact.turnstile_site_key'),
        ]);
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
