<?php

use App\Enums\SkillCategory;
use App\Http\Requests\Dashboard\SkillRequest;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('rules returns the expected validation structure', function () {
    $request = new SkillRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKeys(['name', 'category'])
        ->and($rules['name'])->toContain('required', 'string')
        ->and($rules['category'])->toContain('required');
});

test('name has a maximum length of 100 characters', function () {
    $this->actingAs(createUser());

    $this->post('/dashboard/skills', [
        'name' => str_repeat('a', 101),
        'category' => 'languages',
    ])->assertInvalid('name');

    $this->post('/dashboard/skills', [
        'name' => str_repeat('a', 100),
        'category' => 'languages',
    ])->assertRedirect(route('dashboard.skills.index'));
});

test('name is required', function () {
    $this->actingAs(createUser());

    $this->post('/dashboard/skills', [
        'category' => 'languages',
    ])->assertInvalid('name');
});

test('category is required', function () {
    $this->actingAs(createUser());

    $this->post('/dashboard/skills', [
        'name' => 'Kotlin',
    ])->assertInvalid('category');
});

test('name must be a string', function () {
    $this->actingAs(createUser());

    $this->post('/dashboard/skills', [
        'name' => ['not', 'a', 'string'],
        'category' => 'languages',
    ])->assertInvalid('name');
});

test('category must be a valid enum value', function () {
    $this->actingAs(createUser());

    $this->post('/dashboard/skills', [
        'name' => 'Vue.js',
        'category' => 'invalid-category',
    ])->assertInvalid('category');
});

test('all valid category values are accepted', function (string $category) {
    $this->actingAs(createUser());

    $this->post('/dashboard/skills', [
        'name' => "Skill for {$category}",
        'category' => $category,
    ])->assertRedirect(route('dashboard.skills.index'));

    expect(Skill::where('category', $category)->exists())->toBeTrue();
})->with(fn () => array_column(SkillCategory::cases(), 'value'));

test('updating a skill ignores its own name in unique check', function () {
    $skill = Skill::factory()->create(['name' => 'Go', 'category' => 'languages']);
    $this->actingAs(createUser());

    // Same name and category should pass when updating the same skill
    $this->put("/dashboard/skills/{$skill->id}", [
        'name' => 'Go',
        'category' => 'languages',
    ])->assertRedirect(route('dashboard.skills.index'));

    // But a different skill with same name/category should still fail
    $another = Skill::factory()->create(['name' => 'Rust', 'category' => 'languages']);
    $this->put("/dashboard/skills/{$another->id}", [
        'name' => 'Go',
        'category' => 'languages',
    ])->assertInvalid('name');
});

test('updating a skill can change category without name conflict', function () {
    $skill = Skill::factory()->create(['name' => 'Go', 'category' => 'languages']);
    $this->actingAs(createUser());

    // Change category while keeping name (should pass - unique is per category)
    $this->put("/dashboard/skills/{$skill->id}", [
        'name' => 'Go',
        'category' => 'platform',
    ])->assertRedirect(route('dashboard.skills.index'));

    expect($skill->fresh()->category->value)->toBe('platform');
});

test('name uniqueness is scoped to category', function () {
    Skill::factory()->create(['name' => 'Go', 'category' => 'languages']);
    $this->actingAs(createUser());

    // Same name in different category is allowed
    $this->post('/dashboard/skills', [
        'name' => 'Go',
        'category' => 'platform',
    ])->assertRedirect(route('dashboard.skills.index'));

    expect(Skill::where('name', 'Go')->count())->toBe(2);
});

test('request uses default authorization (no authorize method defined)', function () {
    $request = new SkillRequest;

    // In Laravel 11, FormRequest doesn't define authorize() by default.
    // Authorization is handled via route middleware or policies.
    expect(method_exists($request, 'authorize'))->toBeFalse();
});

// Helper function to create a user for authentication
function createUser()
{
    return User::factory()->create();
}
