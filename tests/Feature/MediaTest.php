<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests are redirected from the media pages', function () {
    $this->get('/dashboard/media')->assertRedirect('/login');

    $this->post('/dashboard/media', [])->assertRedirect('/login');

    $media = Media::factory()->create();
    $this->delete("/dashboard/media/{$media->id}")->assertRedirect('/login');
});

test('store validates file type', function () {
    Storage::fake('media');
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/media', [
        'file' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ])->assertInvalid('file');
});

test('store validates file size', function () {
    Storage::fake('media');
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/media', [
        'file' => UploadedFile::fake()->image('large.png')->size(4100),
    ])->assertInvalid('file');
});

test('store accepts a valid image', function () {
    Storage::fake('media');
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/media', [
        'file' => UploadedFile::fake()->image('test.png', 800, 600),
    ])->assertCreated();
});

test('store persists file and creates media record', function () {
    Storage::fake('media');
    $this->actingAs(User::factory()->create());

    $image = UploadedFile::fake()->image('my-image.png', 800, 600);

    $response = $this->post('/dashboard/media', [
        'file' => $image,
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['id', 'name', 'url', 'mime', 'size', 'created_at']);

    $media = Media::first();

    expect($media)->not->toBeNull()
        ->and($media->name)->toBe('my-image')
        ->and($media->mime)->toBe('image/png')
        ->and($media->size)->toBeGreaterThan(0)
        ->and(Storage::disk('media')->exists($media->path))->toBeTrue()
        ->and(str_starts_with($media->path, 'uploads/'))->toBeTrue();
});

test('index returns json for fetch-style requests (Accept: application/json)', function () {
    Media::factory()->count(2)->create();
    $this->actingAs(User::factory()->create());

    $response = $this->get('/dashboard/media', ['Accept' => 'application/json']);

    $response->assertOk()
        ->assertJsonCount(2)
        ->assertJsonStructure([['id', 'name', 'url', 'mime', 'size', 'created_at']]);
});

test('index returns inertia page for inertia requests and plain navigation', function () {
    Media::factory()->count(3)->create();
    $this->actingAs(User::factory()->create());

    // Simulate an Inertia request with proper headers
    $headers = inertiaHeaders();
    $response = $this->get('/dashboard/media', $headers);

    // Should return Inertia response (JSON with component, props, url, version)
    $response->assertOk();
    $response->assertJsonStructure(['component', 'props', 'url', 'version']);
    $response->assertJsonPath('component', 'dashboard/Media');
    $response->assertJsonPath('props.media.0.name', Media::first()->name);

    // Plain browser navigation (no X-Inertia, no JSON Accept) renders the page
    $this->get('/dashboard/media')
        ->assertOk()
        ->assertSee('dashboard', false)
        ->assertSee('Media');
});

test('destroy removes file and record', function () {
    Storage::fake('media');
    $media = Media::factory()->create(['path' => 'uploads/test-image.png']);
    Storage::disk('media')->put($media->path, 'fake-image-bytes');
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/media/{$media->id}")
        ->assertRedirect(route('dashboard.media.index'));

    expect(Media::find($media->id))->toBeNull()
        ->and(Storage::disk('media')->exists($media->path))->toBeFalse();
});

test('store accepts jpg, jpeg, png, webp, gif, avif, and svg', function (string $extension) {
    Storage::fake('media');
    $this->actingAs(User::factory()->create());

    if ($extension === 'svg') {
        $tmp = tempnam(sys_get_temp_dir(), 'svg');
        file_put_contents($tmp, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>');
        $file = new UploadedFile($tmp, 'test.svg', 'image/svg+xml', null, true);

        $this->post('/dashboard/media', [
            'file' => $file,
        ])->assertCreated();
    } else {
        $this->post('/dashboard/media', [
            'file' => UploadedFile::fake()->image("test.{$extension}", 800, 600),
        ])->assertCreated();
    }
})->with(['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg']);

test('store accepts file at exactly 4096 kilobytes', function () {
    Storage::fake('media');
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/media', [
        'file' => UploadedFile::fake()->image('test.png')->size(4096),
    ])->assertCreated();
});

test('store sanitizes script tags from uploaded svg', function () {
    Storage::fake('media');
    $this->actingAs(User::factory()->create());

    $tmp = tempnam(sys_get_temp_dir(), 'svg');
    file_put_contents($tmp, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/></svg>');
    $file = new UploadedFile($tmp, 'test.svg', 'image/svg+xml', null, true);

    $this->post('/dashboard/media', ['file' => $file])->assertCreated();

    $media = Media::first();
    $stored = Storage::disk('media')->get($media->path);

    expect($stored)->not->toContain('<script>')
        ->and($stored)->toContain('<rect');
});

test('store strips event handler attributes from uploaded svg', function () {
    Storage::fake('media');
    $this->actingAs(User::factory()->create());

    $tmp = tempnam(sys_get_temp_dir(), 'svg');
    file_put_contents($tmp, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" onload="alert(1)"/></svg>');
    $file = new UploadedFile($tmp, 'test.svg', 'image/svg+xml', null, true);

    $this->post('/dashboard/media', ['file' => $file])->assertCreated();

    $media = Media::first();
    $stored = Storage::disk('media')->get($media->path);

    expect($stored)->not->toContain('onload')
        ->and($stored)->toContain('<rect');
});

test('store rejects file with php extension and svg mime type', function () {
    Storage::fake('media');
    $this->actingAs(User::factory()->create());

    $tmp = tempnam(sys_get_temp_dir(), 'php');
    file_put_contents($tmp, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>');
    $file = new UploadedFile($tmp, 'shell.php', 'image/svg+xml', null, true);

    $this->post('/dashboard/media', ['file' => $file])->assertInvalid('file');
});

test('store rejects malformed svg that is not valid xml', function () {
    Storage::fake('media');
    $this->actingAs(User::factory()->create());

    $tmp = tempnam(sys_get_temp_dir(), 'svg');
    file_put_contents($tmp, '<<< not valid xml >>>>');
    $file = new UploadedFile($tmp, 'broken.svg', 'image/svg+xml', null, true);

    $this->post('/dashboard/media', ['file' => $file])->assertInvalid('file');
});
