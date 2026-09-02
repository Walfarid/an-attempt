<?php

use App\Models\PrivacyPolicy;
use App\Models\Profile;
use App\Models\User;

test('public privacy page renders the policy', function () {
    PrivacyPolicy::factory()->create(['body' => '## Disclosure']);

    $this->get('/privacy')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Privacy')
            ->has('policy.body_html')
            ->missing('policy.body')
            ->missing('policy.id')
        );
});

test('analytics scripts are omitted without consent', function () {
    config(['services.clarity.id' => 'TESTID', 'services.google.analytics_id' => 'G-TESTID']);
    Profile::factory()->create();

    $content = $this->get('/')->getContent();

    expect(str_contains($content, 'clarity.ms'))->toBeFalse()
        ->and(str_contains($content, 'googletagmanager.com'))->toBeFalse();
});

test('analytics scripts are omitted when consent is declined', function () {
    config(['services.clarity.id' => 'TESTID', 'services.google.analytics_id' => 'G-TESTID']);
    Profile::factory()->create();

    $content = $this->withUnencryptedCookie('consent', 'declined')->get('/')->getContent();

    expect(str_contains($content, 'clarity.ms'))->toBeFalse()
        ->and(str_contains($content, 'googletagmanager.com'))->toBeFalse();
});

test('analytics scripts load after consent is accepted', function () {
    config(['services.clarity.id' => 'TESTID', 'services.google.analytics_id' => 'G-TESTID']);
    Profile::factory()->create();

    $response = $this->withUnencryptedCookie('consent', 'accepted')->get('/');
    $response->assertOk();

    $content = $response->getContent();

    expect(str_contains($content, 'clarity.ms'))->toBeTrue()
        ->and(str_contains($content, 'googletagmanager.com'))->toBeTrue();
});

test('adsense script is omitted without consent', function () {
    config(['services.adsense.client_id' => 'ca-pub-test123']);
    Profile::factory()->create();

    $content = $this->get('/')->getContent();

    expect(str_contains($content, 'pagead2.googlesyndication.com'))->toBeFalse();
});

test('adsense script loads after consent is accepted', function () {
    config(['services.adsense.client_id' => 'ca-pub-test123']);
    Profile::factory()->create();

    $content = $this->withUnencryptedCookie('consent', 'accepted')->get('/')->getContent();

    expect(str_contains($content, 'pagead2.googlesyndication.com'))->toBeTrue()
        ->and(str_contains($content, 'ca-pub-test123'))->toBeTrue();
});

test('consent mode v2 defaults are present on every page', function () {
    Profile::factory()->create();

    $content = $this->get('/')->getContent();

    expect(str_contains($content, "'consent', 'default'"))->toBeTrue()
        ->and(str_contains($content, "'ad_storage': 'denied'"))->toBeTrue()
        ->and(str_contains($content, "'analytics_storage': 'denied'"))->toBeTrue();
});

test('consent mode update to granted when accepted', function () {
    Profile::factory()->create();

    $content = $this->withUnencryptedCookie('consent', 'accepted')->get('/')->getContent();

    expect(str_contains($content, "'consent', 'update'"))->toBeTrue()
        ->and(str_contains($content, "'ad_storage': 'granted'"))->toBeTrue()
        ->and(str_contains($content, "'analytics_storage': 'granted'"))->toBeTrue();
});

test('guests are redirected from the privacy policy dashboard pages', function () {
    $this->get('/dashboard/privacy/edit')->assertRedirect('/login');
    $this->put('/dashboard/privacy', ['body' => 'x'])->assertRedirect('/login');
});

test('dashboard privacy policy page renders for authenticated users', function () {
    PrivacyPolicy::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard/privacy/edit')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/PrivacyPolicy'));
});

test('authenticated users can update the privacy policy', function () {
    $policy = PrivacyPolicy::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/privacy', ['body' => '## Updated disclosure'])
        ->assertRedirect(route('dashboard.privacy.edit'));

    expect($policy->fresh()->body)->toBe('## Updated disclosure');
});

test('privacy policy body is required', function () {
    PrivacyPolicy::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/privacy', ['body' => ''])->assertInvalid('body');
});

test('public privacy page does not 404 when the policy table is empty', function () {
    expect(PrivacyPolicy::count())->toBe(0);

    $this->get('/privacy')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Privacy'));

    expect(PrivacyPolicy::count())->toBe(1);
});

test('dashboard privacy edit page does not 404 when the policy table is empty', function () {
    expect(PrivacyPolicy::count())->toBe(0);
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard/privacy/edit')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/PrivacyPolicy'));

    expect(PrivacyPolicy::count())->toBe(1);
});
