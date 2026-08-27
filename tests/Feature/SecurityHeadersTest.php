<?php

use App\Models\User;

test('responses include security headers', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
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
