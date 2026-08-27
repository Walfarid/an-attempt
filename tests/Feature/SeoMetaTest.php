<?php

use App\Models\Post;
use App\Models\Profile;

test('the home page renders server-side meta tags for crawlers', function () {
    Profile::factory()->create([
        'name' => 'Walfarid Hermawan Limbong',
        'headline' => 'Software Engineer',
    ]);

    $response = $this->get('/');
    $html = $response->getContent();

    $response->assertOk();

    expect($html)
        ->toContain('name="description"')
        ->toContain('property="og:title"')
        ->toContain('property="og:description"')
        ->toContain('property="og:site_name"')
        ->toContain('content="Walfa"')
        ->toContain('property="og:type"')
        ->toContain('content="website"')
        ->toContain('rel="canonical"')
        ->toContain('name="robots"')
        ->toContain('content="all"')
        ->toContain('name="twitter:card"')
        ->toContain('content="summary_large_image"')
        ->toContain('<title')
        ->toContain('Home - Walfa');
});

test('the home page includes a Person JSON-LD schema', function () {
    Profile::factory()->create([
        'name' => 'Walfarid Hermawan Limbong',
        'headline' => 'Software Engineer',
        'github_url' => 'https://github.com/Walfarid/an-attempt',
    ]);

    $html = $this->get('/')->getContent();

    expect($html)
        ->toContain('application/ld+json')
        ->toContain('"@type":"Person"')
        ->toContain('"name":"Walfarid Hermawan Limbong"')
        ->toContain('"jobTitle":"Software Engineer"')
        ->toContain('"sameAs"')
        ->toContain('https://github.com/Walfarid/an-attempt');
});

test('a blog post renders server-side meta tags with post data', function () {
    $post = Post::factory()->create([
        'title' => 'My Test Post Title',
        'excerpt' => 'A unique test excerpt for this post.',
    ]);

    $html = $this->get("/posts/{$post->slug}")->getContent();

    expect($html)
        ->toContain('My Test Post Title')
        ->toContain('name="description"')
        ->toContain('A unique test excerpt for this post.')
        ->toContain('property="og:title"')
        ->toContain('property="og:type"')
        ->toContain('content="article"');
});

test('the blog index renders server-side meta tags', function () {
    Post::factory()->create(['title' => 'Some Post']);

    $html = $this->get('/posts')->getContent();

    expect($html)
        ->toContain('<title')
        ->toContain('Blog')
        ->toContain('name="description"');
});
