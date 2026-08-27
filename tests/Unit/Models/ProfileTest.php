<?php

use App\Models\Profile;
use App\Support\Markdown;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2025-08-26 12:00:00');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('factory creates a valid profile', function () {
    $profile = Profile::factory()->create();

    expect($profile)->toBeInstanceOf(Profile::class)
        ->and($profile->name)->not->toBeEmpty()
        ->and($profile->headline)->not->toBeEmpty()
        ->and($profile->bio)->not->toBeEmpty()
        ->and($profile->created_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($profile->updated_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('factory generates github url with username', function () {
    $profile = Profile::factory()->create();

    expect($profile->github_url)->toStartWith('https://github.com/')
        ->and($profile->github_url)->not->toBeEmpty();
});

test('factory generates linkedin url with username', function () {
    $profile = Profile::factory()->create();

    expect($profile->linkedin_url)->toStartWith('https://www.linkedin.com/in/')
        ->and($profile->linkedin_url)->not->toBeEmpty();
});

test('fillable attributes are mass assignable', function () {
    $profile = Profile::create([
        'name' => 'John Doe',
        'headline' => 'Full-Stack Developer',
        'bio' => 'A passionate developer.',
        'location' => 'Jakarta, Indonesia',
        'github_url' => 'https://github.com/johndoe',
        'linkedin_url' => 'https://www.linkedin.com/in/johndoe',
        'avatar_path' => 'avatars/john.png',
    ]);

    expect($profile->name)->toBe('John Doe')
        ->and($profile->headline)->toBe('Full-Stack Developer')
        ->and($profile->bio)->toBe('A passionate developer.')
        ->and($profile->location)->toBe('Jakarta, Indonesia')
        ->and($profile->github_url)->toBe('https://github.com/johndoe')
        ->and($profile->linkedin_url)->toBe('https://www.linkedin.com/in/johndoe')
        ->and($profile->avatar_path)->toBe('avatars/john.png');
});

test('id and timestamps are not mass assignable', function () {
    $profile = Profile::create([
        'name' => 'Protected Test',
        'headline' => 'Testing',
        'bio' => 'Testing mass assignment.',
        'id' => 999,
    ]);

    expect($profile->id)->not->toBe(999);
});

test('location can be null', function () {
    $profile = Profile::factory()->create(['location' => null]);

    expect($profile->fresh()->location)->toBeNull();
});

test('github_url can be null', function () {
    $profile = Profile::factory()->create(['github_url' => null]);

    expect($profile->fresh()->github_url)->toBeNull();
});

test('linkedin_url can be null', function () {
    $profile = Profile::factory()->create(['linkedin_url' => null]);

    expect($profile->fresh()->linkedin_url)->toBeNull();
});

test('avatar_path can be null', function () {
    $profile = Profile::factory()->create(['avatar_path' => null]);

    expect($profile->fresh()->avatar_path)->toBeNull();
});

test('name is required', function () {
    $profile = Profile::factory()->make(['name' => null]);

    expect(fn () => $profile->save())->toThrow(QueryException::class);
});

test('headline is required', function () {
    $profile = Profile::factory()->make(['headline' => null]);

    expect(fn () => $profile->save())->toThrow(QueryException::class);
});

test('bio is required', function () {
    $profile = Profile::factory()->make(['bio' => null]);

    expect(fn () => $profile->save())->toThrow(QueryException::class);
});

test('created_at and updated_at are automatically set', function () {
    $profile = Profile::factory()->create();

    expect($profile->created_at)->not->toBeNull()
        ->and($profile->updated_at)->not->toBeNull()
        ->and($profile->created_at->format('Y-m-d'))->toBe('2025-08-26');
});

test('timestamps are updated when model is modified', function () {
    $profile = Profile::factory()->create();
    $originalUpdatedAt = $profile->updated_at;

    // Travel forward in time
    CarbonImmutable::setTestNow('2025-08-27 10:00:00');

    $profile->update(['name' => 'Updated Name']);

    expect($profile->fresh()->updated_at->format('Y-m-d'))->toBe('2025-08-27');
});

test('current returns the singleton profile', function () {
    $profile = Profile::factory()->create(['name' => 'Singleton Profile']);

    $current = Profile::current();

    expect($current->id)->toBe($profile->id)
        ->and($current->name)->toBe('Singleton Profile');
});

test('current throws exception when no profile exists', function () {
    expect(fn () => Profile::current())->toThrow(ModelNotFoundException::class);
});

test('current always returns the first profile regardless of how many exist', function () {
    $first = Profile::factory()->create(['name' => 'First Profile']);
    $second = Profile::factory()->create(['name' => 'Second Profile']);

    $current = Profile::current();

    expect($current->id)->toBe($first->id)
        ->and($current->name)->toBe('First Profile');
});

test('bioHtml converts markdown to html', function () {
    $profile = Profile::factory()->make([
        'bio' => "# About Me\n\nI am a **developer**.",
    ]);

    expect($profile->bioHtml())->toContain('<h1>About Me</h1>')
        ->and($profile->bioHtml())->toContain('<strong>developer</strong>');
});

test('bioHtml strips unsafe content', function () {
    $profile = Profile::factory()->make([
        'bio' => '<script>alert(1)</script> [link](javascript:void(0))',
    ]);

    expect($profile->bioHtml())->not->toContain('<script>')
        ->and($profile->bioHtml())->not->toContain('javascript:');
});

test('bioHtml strips raw html from bio', function () {
    $profile = Profile::factory()->make([
        'bio' => '<div>Should be stripped</div> **bold**',
    ]);

    expect($profile->bioHtml())->not->toContain('<div>')
        ->and($profile->bioHtml())->not->toContain('Should be stripped');
});

test('bioHtml handles empty bio', function () {
    $profile = Profile::factory()->make(['bio' => '']);

    expect($profile->bioHtml())->toBe('');
});

test('bioHtml handles plain text bio', function () {
    $profile = Profile::factory()->make(['bio' => 'Just plain text without markdown.']);

    expect($profile->bioHtml())->toContain('Just plain text without markdown.');
});

test('bioHtml handles complex markdown', function () {
    $profile = Profile::factory()->make([
        'bio' => <<<'MARKDOWN'
# Heading 1
## Heading 2

- Item 1
- Item 2

1. Numbered
2. List

> Blockquote

`inline code`

```
code block
```

[link](https://example.com)
MARKDOWN,
    ]);

    $html = $profile->bioHtml();

    expect($html)->toContain('<h1>Heading 1</h1>')
        ->and($html)->toContain('<h2>Heading 2</h2>')
        ->and($html)->toContain('<ul>')
        ->and($html)->toContain('<ol>')
        ->and($html)->toContain('<blockquote>')
        ->and($html)->toContain('<code>')
        ->and($html)->toContain('<a href="https://example.com">');
});

test('bioHtml uses Markdown support class', function () {
    $profile = Profile::factory()->make(['bio' => '# Test']);

    // Verify it produces the same output as the Markdown support class
    expect($profile->bioHtml())->toBe(Markdown::toHtml('# Test'));
});

test('profile can be updated with new values', function () {
    $profile = Profile::factory()->create();

    $profile->update([
        'name' => 'New Name',
        'headline' => 'New Headline',
        'location' => 'New Location',
    ]);

    $fresh = $profile->fresh();

    expect($fresh->name)->toBe('New Name')
        ->and($fresh->headline)->toBe('New Headline')
        ->and($fresh->location)->toBe('New Location');
});

test('profile can clear optional fields', function () {
    $profile = Profile::factory()->create([
        'location' => 'Jakarta',
        'github_url' => 'https://github.com/user',
        'linkedin_url' => 'https://linkedin.com/in/user',
        'avatar_path' => 'avatars/user.png',
    ]);

    $profile->update([
        'location' => null,
        'github_url' => null,
        'linkedin_url' => null,
        'avatar_path' => null,
    ]);

    $fresh = $profile->fresh();

    expect($fresh->location)->toBeNull()
        ->and($fresh->github_url)->toBeNull()
        ->and($fresh->linkedin_url)->toBeNull()
        ->and($fresh->avatar_path)->toBeNull();
});

test('model serializes correctly to array', function () {
    $profile = Profile::factory()->create([
        'name' => 'Serialize Test',
        'location' => 'Test Location',
        'avatar_path' => 'avatars/test.png',
    ]);

    $array = $profile->toArray();

    expect($array)->toHaveKey('id')
        ->and($array)->toHaveKey('name')
        ->and($array)->toHaveKey('headline')
        ->and($array)->toHaveKey('bio')
        ->and($array)->toHaveKey('location')
        ->and($array)->toHaveKey('github_url')
        ->and($array)->toHaveKey('linkedin_url')
        ->and($array)->toHaveKey('avatar_path')
        ->and($array)->toHaveKey('created_at')
        ->and($array)->toHaveKey('updated_at');
});

test('model serializes correctly to json', function () {
    $profile = Profile::factory()->create([
        'name' => 'JSON Test',
        'headline' => 'JSON Headline',
    ]);

    $json = json_encode($profile);
    $decoded = json_decode($json, true);

    expect($decoded)->toHaveKey('name')
        ->and($decoded['name'])->toBe('JSON Test')
        ->and($decoded)->toHaveKey('headline')
        ->and($decoded['headline'])->toBe('JSON Headline');
});

test('profile can have long bio', function () {
    $longBio = str_repeat('This is a paragraph about my experience. ', 100);

    $profile = Profile::factory()->create(['bio' => $longBio]);

    expect($profile->fresh()->bio)->toBe($longBio);
});

test('profile stores github_url as string', function () {
    $profile = Profile::factory()->create(['github_url' => 'https://github.com/user']);

    expect($profile->github_url)->toBeString()
        ->and($profile->github_url)->toBe('https://github.com/user');
});

test('profile stores linkedin_url as string', function () {
    $profile = Profile::factory()->create(['linkedin_url' => 'https://www.linkedin.com/in/user']);

    expect($profile->linkedin_url)->toBeString()
        ->and($profile->linkedin_url)->toBe('https://www.linkedin.com/in/user');
});

test('profile can be deleted', function () {
    $profile = Profile::factory()->create();

    $profile->delete();

    expect(Profile::where('id', $profile->id)->exists())->toBeFalse();
});

test('deleting profile allows creating a new one', function () {
    $profile = Profile::factory()->create();
    $profile->delete();

    $newProfile = Profile::factory()->create(['name' => 'New Profile']);

    expect($newProfile->name)->toBe('New Profile')
        ->and(Profile::count())->toBe(1);
});

test('factory respects explicitly set name', function () {
    $profile = Profile::factory()->create(['name' => 'Custom Name']);

    expect($profile->name)->toBe('Custom Name');
});

test('factory respects explicitly set headline', function () {
    $profile = Profile::factory()->create(['headline' => 'Custom Headline']);

    expect($profile->headline)->toBe('Custom Headline');
});

test('factory respects explicitly set bio', function () {
    $profile = Profile::factory()->create(['bio' => 'Custom bio content.']);

    expect($profile->bio)->toBe('Custom bio content.');
});

test('factory creates bio with multiple paragraphs', function () {
    $profile = Profile::factory()->create();

    // The factory uses fake()->paragraphs(2, true) which creates 2 paragraphs
    expect($profile->bio)->toContain("\n\n");
});
