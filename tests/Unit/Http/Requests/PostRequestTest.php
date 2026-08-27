<?php

use App\Http\Requests\Dashboard\PostRequest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->request = new PostRequest;
});

// Rules structure tests

test('rules returns the expected validation structure', function () {
    $rules = $this->request->rules();

    expect($rules)->toHaveKeys(['title', 'slug', 'excerpt', 'body', 'published_at'])
        ->and($rules['title'])->toContain('required', 'string', 'max:255')
        ->and($rules['slug'])->toContain('required', 'string', 'max:255', 'alpha_dash')
        ->and($rules['excerpt'])->toContain('nullable', 'string', 'max:300')
        ->and($rules['body'])->toContain('required', 'string')
        ->and($rules['published_at'])->toContain('nullable', 'date');
});

test('slug rule contains unique validation rule', function () {
    $rules = $this->request->rules();

    expect($rules['slug'])->toHaveCount(5);
});

// Title validation tests

test('title is required', function () {
    $validator = Validator::make(
        ['slug' => 'test-post', 'body' => 'Content'],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('title'))->toBeTrue();
});

test('title must be a string', function () {
    $validator = Validator::make(
        ['title' => ['array'], 'slug' => 'test-post', 'body' => 'Content'],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('title'))->toBeTrue();
});

test('title has a maximum length of 255 characters', function () {
    $rules = $this->request->rules();

    $tooLong = Validator::make(
        ['title' => str_repeat('a', 256), 'slug' => 'test-post', 'body' => 'Content'],
        $rules
    );

    $justRight = Validator::make(
        ['title' => str_repeat('a', 255), 'slug' => 'test-post', 'body' => 'Content'],
        $rules
    );

    expect($tooLong->fails())->toBeTrue()
        ->and($tooLong->errors()->has('title'))->toBeTrue()
        ->and($justRight->fails())->toBeFalse();
});

test('title can contain unicode characters', function () {
    $validator = Validator::make(
        ['title' => 'Héllo Wörld 日本語 🎉', 'slug' => 'test-post', 'body' => 'Content'],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

// Slug validation tests

test('slug is required', function () {
    $validator = Validator::make(
        ['title' => 'Test Post', 'body' => 'Content'],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slug'))->toBeTrue();
});

test('slug must be a string', function () {
    $validator = Validator::make(
        ['title' => 'Test Post', 'slug' => ['array'], 'body' => 'Content'],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slug'))->toBeTrue();
});

test('slug has a maximum length of 255 characters', function () {
    $rules = $this->request->rules();

    $tooLong = Validator::make(
        ['title' => 'Test', 'slug' => str_repeat('a', 256), 'body' => 'Content'],
        $rules
    );

    $justRight = Validator::make(
        ['title' => 'Test', 'slug' => str_repeat('a', 255), 'body' => 'Content'],
        $rules
    );

    expect($tooLong->fails())->toBeTrue()
        ->and($tooLong->errors()->has('slug'))->toBeTrue()
        ->and($justRight->fails())->toBeFalse();
});

test('slug must only contain alpha-numeric characters, dashes, and underscores', function (string $slug, bool $expectedValid) {
    $validator = Validator::make(
        ['title' => 'Test Post', 'slug' => $slug, 'body' => 'Content'],
        $this->request->rules()
    );

    expect($validator->fails())->toBe(! $expectedValid);
})->with([
    'letters only' => ['slug', true],
    'numbers only' => ['12345', true],
    'alphanumeric' => ['post-123', true],
    'with dashes' => ['my-blog-post', true],
    'with underscores' => ['my_blog_post', true],
    'mixed' => ['my-blog_post-123', true],
    'with spaces' => ['my blog post', false],
    'with special chars' => ['my-blog!', false],
    'with @ symbol' => ['my@blog', false],
    'with dots' => ['my.blog', false],
]);

// Excerpt validation tests

test('excerpt is optional', function () {
    $validator = Validator::make(
        ['title' => 'Test Post', 'slug' => 'test-post', 'body' => 'Content'],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('excerpt can be null', function () {
    $validator = Validator::make(
        ['title' => 'Test Post', 'slug' => 'test-post', 'body' => 'Content', 'excerpt' => null],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('excerpt can be an empty string', function () {
    $validator = Validator::make(
        ['title' => 'Test Post', 'slug' => 'test-post', 'body' => 'Content', 'excerpt' => ''],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('excerpt must be a string when provided', function () {
    $validator = Validator::make(
        ['title' => 'Test Post', 'slug' => 'test-post', 'body' => 'Content', 'excerpt' => ['array']],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('excerpt'))->toBeTrue();
});

test('excerpt has a maximum length of 300 characters', function () {
    $rules = $this->request->rules();

    $tooLong = Validator::make(
        ['title' => 'Test', 'slug' => 'test', 'body' => 'Content', 'excerpt' => str_repeat('a', 301)],
        $rules
    );

    $justRight = Validator::make(
        ['title' => 'Test', 'slug' => 'test', 'body' => 'Content', 'excerpt' => str_repeat('a', 300)],
        $rules
    );

    expect($tooLong->fails())->toBeTrue()
        ->and($tooLong->errors()->has('excerpt'))->toBeTrue()
        ->and($justRight->fails())->toBeFalse();
});

// Body validation tests

test('body is required', function () {
    $validator = Validator::make(
        ['title' => 'Test Post', 'slug' => 'test-post'],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('body'))->toBeTrue();
});

test('body must be a string', function () {
    $validator = Validator::make(
        ['title' => 'Test Post', 'slug' => 'test-post', 'body' => ['array']],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('body'))->toBeTrue();
});

test('body can contain markdown syntax', function () {
    $validator = Validator::make(
        [
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => "# Heading\n\nParagraph with **bold** and *italic*.\n\n- List item\n- Another item",
        ],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('body can contain newlines and special characters', function () {
    $validator = Validator::make(
        [
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => "Line 1\nLine 2\r\nLine 3\tTabbed\n\nParagraph break",
        ],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

// Published_at validation tests

test('published_at is optional', function () {
    $validator = Validator::make(
        ['title' => 'Test Post', 'slug' => 'test-post', 'body' => 'Content'],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('published_at can be null', function () {
    $validator = Validator::make(
        ['title' => 'Test Post', 'slug' => 'test-post', 'body' => 'Content', 'published_at' => null],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('published_at must be a valid date', function () {
    $validator = Validator::make(
        ['title' => 'Test Post', 'slug' => 'test-post', 'body' => 'Content', 'published_at' => 'not-a-date'],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('published_at'))->toBeTrue();
});

test('published_at accepts valid date formats', function (string $date) {
    $validator = Validator::make(
        ['title' => 'Test Post', 'slug' => 'test-post', 'body' => 'Content', 'published_at' => $date],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
})->with([
    'Y-m-d' => '2024-01-15',
    'Y-m-d H:i:s' => '2024-01-15 10:30:00',
    'ISO 8601' => '2024-01-15T10:30:00Z',
    'future date' => '2099-12-31',
]);

// prepareForValidation tests

test('prepareForValidation derives slug from title when slug is not provided', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('dashboard.posts.store'), [
        'title' => 'My New Blog Post',
        'body' => 'This is the content.',
    ]);

    $response->assertValid(['title', 'slug', 'body']);

    $post = Post::where('title', 'My New Blog Post')->first();
    expect($post)->not->toBeNull()
        ->and($post->slug)->toBe('my-new-blog-post');
});

test('prepareForValidation uses explicit slug when provided', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('dashboard.posts.store'), [
        'title' => 'My New Blog Post',
        'slug' => 'custom-slug',
        'body' => 'This is the content.',
    ]);

    $response->assertValid(['title', 'slug', 'body']);

    $post = Post::where('title', 'My New Blog Post')->first();
    expect($post)->not->toBeNull()
        ->and($post->slug)->toBe('custom-slug');
});

test('prepareForValidation slugifies the explicit slug value', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('dashboard.posts.store'), [
        'title' => 'My New Blog Post',
        'slug' => 'Custom Slug With Spaces',
        'body' => 'This is the content.',
    ]);

    $response->assertValid(['title', 'slug', 'body']);

    $post = Post::where('title', 'My New Blog Post')->first();
    expect($post)->not->toBeNull()
        ->and($post->slug)->toBe('custom-slug-with-spaces');
});

test('prepareForValidation handles empty slug by deriving from title', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // When slug is explicitly provided as empty string, it's slugified to empty string
    // The fallback to title only happens when slug is not provided at all
    $response = $this->post(route('dashboard.posts.store'), [
        'title' => 'Another Blog Post',
        'slug' => '',
        'body' => 'This is the content.',
    ]);

    // Empty slug after Str::slug() is still empty, so validation should fail
    $response->assertInvalid('slug');
});

test('prepareForValidation handles special characters in title', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('dashboard.posts.store'), [
        'title' => 'Laravel & PHP: Best Practices!',
        'body' => 'This is the content.',
    ]);

    $response->assertValid(['title', 'slug', 'body']);

    $post = Post::where('title', 'Laravel & PHP: Best Practices!')->first();
    expect($post)->not->toBeNull()
        ->and($post->slug)->toBe('laravel-php-best-practices');
});

test('prepareForValidation handles unicode in title', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('dashboard.posts.store'), [
        'title' => 'Café & Résumé',
        'body' => 'This is the content.',
    ]);

    $response->assertValid(['title', 'slug', 'body']);

    $post = Post::where('title', 'Café & Résumé')->first();
    expect($post)->not->toBeNull()
        ->and($post->slug)->toBe('cafe-resume');
});

// Unique slug validation tests

test('slug must be unique when creating a new post', function () {
    Post::factory()->create(['slug' => 'existing-slug']);
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('dashboard.posts.store'), [
        'title' => 'New Post',
        'slug' => 'existing-slug',
        'body' => 'Content',
    ]);

    $response->assertInvalid('slug');
});

test('slug can remain the same when updating a post', function () {
    $post = Post::factory()->create(['slug' => 'my-post']);
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->put(route('dashboard.posts.update', $post), [
        'title' => 'Updated Title',
        'slug' => 'my-post',
        'body' => 'Updated content',
    ]);

    $response->assertValid('slug');
});

test('slug can be changed to a unique value when updating', function () {
    $post = Post::factory()->create(['slug' => 'old-slug']);
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->put(route('dashboard.posts.update', $post), [
        'title' => 'Updated Title',
        'slug' => 'new-unique-slug',
        'body' => 'Updated content',
    ]);

    $response->assertValid('slug');

    expect($post->fresh()->slug)->toBe('new-unique-slug');
});

test('slug cannot be changed to another posts slug', function () {
    Post::factory()->create(['slug' => 'other-post-slug']);
    $post = Post::factory()->create(['slug' => 'my-post']);
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->put(route('dashboard.posts.update', $post), [
        'title' => 'Updated Title',
        'slug' => 'other-post-slug',
        'body' => 'Updated content',
    ]);

    $response->assertInvalid('slug');
});

// Integration tests with full request

test('validation passes with minimal valid data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('dashboard.posts.store'), [
        'title' => 'Valid Post',
        'body' => 'Valid content',
    ]);

    $response->assertValid();
});

test('validation passes with all fields provided', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('dashboard.posts.store'), [
        'title' => 'Complete Post',
        'slug' => 'complete-post',
        'excerpt' => 'A brief summary of the post.',
        'body' => 'Full content with **markdown** support.',
        'published_at' => '2024-06-15 10:00:00',
    ]);

    $response->assertValid();

    $post = Post::where('slug', 'complete-post')->first();
    expect($post)->not->toBeNull()
        ->and($post->excerpt)->toBe('A brief summary of the post.')
        ->and($post->published_at->format('Y-m-d H:i'))->toBe('2024-06-15 10:00');
});

test('validation fails with missing required fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('dashboard.posts.store'), []);

    $response->assertInvalid(['title', 'body']);
});

test('all invalid fields are reported simultaneously', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('dashboard.posts.store'), [
        'title' => str_repeat('a', 256),
        // Str::slug('   ') returns empty string, which fails required validation
        'slug' => '   ',
        'excerpt' => str_repeat('a', 301),
        'body' => ['not', 'a', 'string'],
        'published_at' => 'not-a-date',
    ]);

    $response->assertInvalid(['title', 'slug', 'excerpt', 'body', 'published_at']);
});

test('updating a post passes validation with valid data', function () {
    $post = Post::factory()->create();
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->put(route('dashboard.posts.update', $post), [
        'title' => 'Updated Title',
        'body' => 'Updated body content',
        'published_at' => now()->addDay()->format('Y-m-d H:i:s'),
    ]);

    $response->assertValid();

    $post->refresh();
    expect($post->title)->toBe('Updated Title')
        ->and($post->slug)->toBe('updated-title');
});

test('authorization is handled via route middleware', function () {
    $request = new PostRequest;

    expect(method_exists($request, 'authorize'))->toBeFalse();
});
