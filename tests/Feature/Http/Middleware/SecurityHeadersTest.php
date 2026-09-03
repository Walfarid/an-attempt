<?php

use App\Models\Post;
use App\Models\User;

$standardHeaders = [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
];

$assertStandardHeaders = function ($response) use ($standardHeaders) {
    foreach ($standardHeaders as $header => $value) {
        $response->assertHeader($header, $value);
    }
};

test('responses include security headers', function () use ($assertStandardHeaders) {
    $assertStandardHeaders($this->get('/'));
});

test('HSTS header is only set in production', function () {
    $response = $this->get('/');

    if (app()->isProduction()) {
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    } else {
        $response->assertHeaderMissing('Strict-Transport-Security');
    }
});

test('security headers are present on authenticated routes', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get('/dashboard');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
});

test('error responses still carry security headers', function () use ($assertStandardHeaders) {
    $response = $this->get('/nonexistent-page');

    $response->assertStatus(404);
    $assertStandardHeaders($response);
});

test('public post pages include security headers', function () use ($assertStandardHeaders) {
    $post = Post::factory()->create();

    $response = $this->get("/posts/{$post->slug}");

    $response->assertOk();
    $assertStandardHeaders($response);
});
