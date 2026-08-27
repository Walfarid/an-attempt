<?php

use App\Models\Profile;
use Illuminate\Support\Facades\View;

test('appearance is shared with views from cookie', function () {
    Profile::factory()->create();

    $response = $this->withUnencryptedCookie('appearance', 'dark')->get('/');

    expect(View::shared('appearance'))->toBe('dark');
});

test('appearance defaults to system when no cookie is set', function () {
    Profile::factory()->create();

    $response = $this->get('/');

    expect(View::shared('appearance'))->toBe('system');
});

test('appearance middleware runs on all web routes', function () {
    Profile::factory()->create();

    // The middleware should run and share the appearance value
    $this->get('/')->assertOk();

    expect(View::shared('appearance'))->not->toBeNull();
});

test('appearance value is available in blade views', function () {
    Profile::factory()->create();

    $this->withUnencryptedCookie('appearance', 'light')->get('/');

    // The shared 'appearance' variable should be 'light'
    expect(View::shared('appearance'))->toBe('light');
});

test('appearance accepts valid values', function (string $value) {
    Profile::factory()->create();

    $response = $this->withUnencryptedCookie('appearance', $value)->get('/');

    expect(View::shared('appearance'))->toBe($value);
})->with(['light', 'dark', 'system']);

test('appearance cookie is excluded from encryption', function () {
    // Verify the cookie is configured to be excluded from encryption
    // This is set in bootstrap/app.php: $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
    // The cookie is excluded from encryption so JavaScript can read and write it directly
    Profile::factory()->create();

    $response = $this->withUnencryptedCookie('appearance', 'dark')->get('/');

    expect(View::shared('appearance'))->toBe('dark');
});
