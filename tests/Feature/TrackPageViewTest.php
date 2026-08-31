<?php

use App\Models\PageView;
use App\Models\Post;
use App\Models\Profile;

test('page views are recorded for public pages', function () {
    Profile::factory()->create();

    $this->get('/')->assertOk();

    expect(PageView::where('path', '/')->count())->toBe(1);
});

test('machine-facing resources are not recorded as page views', function () {
    Profile::factory()->create();
    Post::factory()->create(['published_at' => now()->subDay()]);

    $this->get('/sitemap.xml')->assertOk();

    expect(PageView::where('path', 'sitemap.xml')->count())->toBe(0);
});
