<?php

test('ads.txt returns content with correct content type and cache headers', function () {
    $content = 'google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0';
    config()->set('services.ads.txt', $content);

    $this->get('/ads.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
        ->assertHeader('Cache-Control', 'max-age=60, must-revalidate, public, stale-while-revalidate=300')
        ->assertSee($content."\n", false);
});

test('ads.txt returns 404 when content is not configured', function () {
    config()->set('services.ads.txt', null);

    $this->get('/ads.txt')->assertNotFound();
});
