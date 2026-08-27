<?php

use App\Enums\SkillCategory;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('factory creates a valid skill', function () {
    $skill = Skill::factory()->create();

    expect($skill)->toBeInstanceOf(Skill::class)
        ->and($skill->id)->toBeInt()
        ->and($skill->name)->toBeString()
        ->and($skill->category)->toBeInstanceOf(SkillCategory::class);
});

test('skill has fillable attributes', function () {
    $skill = Skill::factory()->make([
        'name' => 'TypeScript',
        'category' => SkillCategory::Languages,
    ]);

    expect($skill->name)->toBe('TypeScript')
        ->and($skill->category)->toBe(SkillCategory::Languages);
});

test('category is cast to SkillCategory enum', function () {
    $skill = Skill::factory()->create(['category' => 'frameworks']);

    expect($skill->category)->toBeInstanceOf(SkillCategory::class)
        ->and($skill->category)->toBe(SkillCategory::Frameworks);

    $skill = Skill::factory()->create(['category' => SkillCategory::Databases]);

    expect($skill->category)->toBe(SkillCategory::Databases);
});

test('all category enum values can be used', function (SkillCategory $category) {
    $skill = Skill::factory()->create(['category' => $category]);

    expect($skill->fresh()->category)->toBe($category);
})->with(SkillCategory::cases());

test('skill can belong to many projects', function () {
    $skill = Skill::factory()->create(['name' => 'Laravel']);
    $project = Project::factory()->create();

    $skill->projects()->attach($project);

    expect($skill->projects)->toHaveCount(1)
        ->and($skill->projects->first()->id)->toBe($project->id);
});

test('skill can belong to multiple projects', function () {
    $skill = Skill::factory()->create(['name' => 'Vue']);
    $projects = Project::factory()->count(3)->create();

    $skill->projects()->attach($projects);

    expect($skill->projects)->toHaveCount(3)
        ->and($skill->projects->pluck('id'))->toEqual($projects->pluck('id'));
});

test('projects relationship is a belongsToMany relationship', function () {
    $skill = new Skill;

    $relation = $skill->projects();

    expect($relation)->toBeInstanceOf(BelongsToMany::class);
});

test('detaching a project removes it from skill projects', function () {
    $skill = Skill::factory()->create(['name' => 'Docker']);
    $project = Project::factory()->create();
    $skill->projects()->attach($project);

    $skill->projects()->detach($project);

    expect($skill->fresh()->projects)->toBeEmpty();
});

test('syncing projects replaces existing relationships', function () {
    $skill = Skill::factory()->create(['name' => 'React']);
    $oldProject = Project::factory()->create();
    $newProjects = Project::factory()->count(2)->create();
    $skill->projects()->attach($oldProject);

    $skill->projects()->sync($newProjects->pluck('id'));

    $skillProjects = $skill->fresh()->projects;
    expect($skillProjects)->toHaveCount(2)
        ->and($skillProjects->contains($oldProject))->toBeFalse();
});

test('name and category together must be unique', function () {
    Skill::factory()->create([
        'name' => 'Go',
        'category' => SkillCategory::Languages,
    ]);

    // Same name + category should fail
    $duplicate = Skill::factory()->make([
        'name' => 'Go',
        'category' => SkillCategory::Languages,
    ]);

    expect(fn () => $duplicate->save())->toThrow(QueryException::class);
});

test('same name is allowed in different categories', function () {
    Skill::factory()->create([
        'name' => 'Go',
        'category' => SkillCategory::Languages,
    ]);

    $skill = Skill::factory()->create([
        'name' => 'Go',
        'category' => SkillCategory::Platform,
    ]);

    expect($skill)->toBeInstanceOf(Skill::class)
        ->and(Skill::where('name', 'Go')->count())->toBe(2);
});

test('timestamps are automatically set on creation', function () {
    $skill = Skill::factory()->create();

    expect($skill->created_at)->not->toBeNull()
        ->and($skill->updated_at)->not->toBeNull();
});

test('updating a skill updates the timestamp', function () {
    $skill = Skill::factory()->create(['name' => 'Original']);
    $originalUpdatedAt = $skill->updated_at;

    sleep(1); // Ensure time difference
    $skill->update(['name' => 'Updated Name']);

    expect($skill->updated_at->timestamp)->toBeGreaterThan($originalUpdatedAt->timestamp);
});

test('skill can be found by name and category', function () {
    $skill = Skill::factory()->create([
        'name' => 'Laravel',
        'category' => SkillCategory::Frameworks,
    ]);

    $found = Skill::where('name', 'Laravel')
        ->where('category', SkillCategory::Frameworks->value)
        ->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($skill->id);
});

test('deleting a skill removes it from projects', function () {
    $skill = Skill::factory()->create(['name' => 'Node.js']);
    $project = Project::factory()->create();
    $skill->projects()->attach($project);

    $skill->delete();

    expect(Skill::find($skill->id))->toBeNull()
        ->and($project->fresh()->skills)->toBeEmpty();
});

test('name is required', function () {
    $skill = Skill::factory()->make(['name' => null]);

    expect(fn () => $skill->save())->toThrow(QueryException::class);
});

test('category is required', function () {
    $skill = Skill::factory()->make(['category' => null]);

    expect(fn () => $skill->save())->toThrow(QueryException::class);
});

test('model serializes correctly to array', function () {
    $skill = Skill::factory()->create([
        'name' => 'TypeScript',
        'category' => SkillCategory::Languages,
    ]);

    $array = $skill->toArray();

    expect($array)->toHaveKey('id')
        ->and($array)->toHaveKey('name')
        ->and($array)->toHaveKey('category')
        ->and($array)->toHaveKey('created_at')
        ->and($array)->toHaveKey('updated_at');
});

test('model serializes correctly to json', function () {
    $skill = Skill::factory()->create([
        'name' => 'Python',
        'category' => SkillCategory::Languages,
    ]);

    $json = json_encode($skill);
    $decoded = json_decode($json, true);

    expect($decoded)->toHaveKey('name')
        ->and($decoded['name'])->toBe('Python')
        ->and($decoded)->toHaveKey('category')
        ->and($decoded['category'])->toBe('languages');
});
