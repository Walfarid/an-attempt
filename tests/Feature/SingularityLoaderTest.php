<?php

use App\Models\Profile;

test('the page draw loader component exists with its self-drawing mechanics', function () {
    $component = file_get_contents(
        resource_path('js/components/site/PageDrawLoader.vue'),
    );

    expect($component)->not->toBeFalse()
        // GSAP drives the self-drawing animation...
        ->and($component)->toContain('gsap')
        // It measures the real DOM layout...
        ->and($component)->toContain('getBoundingClientRect')
        // It draws SVG strokes around page regions...
        ->and($component)->toContain('strokeDashoffset')
        // It includes a top progress bar...
        ->and($component)->toContain('topBar');
});

test('the app entry mounts the page draw loader outside the inertia root', function () {
    $entry = file_get_contents(resource_path('js/app.ts'));

    expect($entry)->toContain('PageDrawLoader')
        // The loader gets its own root element, separate from #app...
        ->and($entry)->toContain('page-loader-root')
        // Inertia progress events are enabled for the loader...
        ->and($entry)->toContain('progress:');
});

test('the page draw loader is bundled into the eager entry point', function () {
    $manifest = public_path('build/manifest.json');

    if (! file_exists($manifest)) {
        $this->markTestSkipped('Run `npm run build` to generate the Vite manifest.');
    }

    $manifest = json_decode(file_get_contents($manifest), true);
    $entry = $manifest['resources/js/app.ts']['file'] ?? null;

    expect($entry)->not->toBeNull();

    $bundle = file_get_contents(public_path("build/{$entry}"));

    expect($bundle)->toContain('PageDrawLoader');
});

test('the home page still renders underneath the boot loader', function () {
    Profile::factory()->create();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Welcome'));
});
