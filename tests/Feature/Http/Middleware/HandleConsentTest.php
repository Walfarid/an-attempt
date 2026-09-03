<?php

use App\Models\Profile;
use Illuminate\Support\Facades\View;

test('consent is shared with views from cookie', function () {
    Profile::factory()->create();

    $this->withUnencryptedCookie('consent', 'accepted')->get('/');

    expect(View::shared('consent'))->toBe('accepted');
});

test('consent defaults to unset when no cookie is present', function () {
    Profile::factory()->create();

    $this->get('/');

    expect(View::shared('consent'))->toBe('unset');
});

test('consent accepts declined value', function () {
    Profile::factory()->create();

    $this->withUnencryptedCookie('consent', 'declined')->get('/');

    expect(View::shared('consent'))->toBe('declined');
});

test('consent cookie is excluded from encryption so JS can read it', function () {
    Profile::factory()->create();

    // The cookie is deliberately excluded from encryption (bootstrap/app.php
    // encryptCookies except list) so the client-side banner can read and write
    // it directly — an encrypted value would never reach the middleware.
    $this->withUnencryptedCookie('consent', 'accepted')->get('/');

    expect(View::shared('consent'))->toBe('accepted');
});

test('consent middleware runs on all web routes', function () {
    Profile::factory()->create();

    $this->get('/')->assertOk();

    expect(View::shared('consent'))->not->toBeNull();
});
