<?php

use App\Enums\SkillCategory;

test('enum has all expected cases', function () {
    $cases = SkillCategory::cases();

    expect($cases)->toHaveCount(6)
        ->and($cases[0])->toBe(SkillCategory::Languages)
        ->and($cases[1])->toBe(SkillCategory::Frameworks)
        ->and($cases[2])->toBe(SkillCategory::Databases)
        ->and($cases[3])->toBe(SkillCategory::Devops)
        ->and($cases[4])->toBe(SkillCategory::Platform)
        ->and($cases[5])->toBe(SkillCategory::Security);
});

test('enum cases have correct values', function () {
    expect(SkillCategory::Languages->value)->toBe('languages')
        ->and(SkillCategory::Frameworks->value)->toBe('frameworks')
        ->and(SkillCategory::Databases->value)->toBe('databases')
        ->and(SkillCategory::Devops->value)->toBe('devops')
        ->and(SkillCategory::Platform->value)->toBe('platform')
        ->and(SkillCategory::Security->value)->toBe('security');
});

test('values method returns associative array of case values', function () {
    $values = SkillCategory::values();

    expect($values)->toBeArray()
        ->toHaveCount(6)
        ->toBe([
            'languages' => 'languages',
            'frameworks' => 'frameworks',
            'databases' => 'databases',
            'devops' => 'devops',
            'platform' => 'platform',
            'security' => 'security',
        ]);
});

test('values method returns keys and values as identical strings', function () {
    $values = SkillCategory::values();

    foreach ($values as $key => $value) {
        expect($key)->toBe($value);
    }
});

test('enum is backed by string type', function () {
    $reflection = new ReflectionEnum(SkillCategory::class);

    expect($reflection->isBacked())->toBeTrue()
        ->and($reflection->getBackingType()?->getName())->toBe('string');
});

test('enum can be instantiated from string value', function () {
    expect(SkillCategory::from('languages'))->toBe(SkillCategory::Languages)
        ->and(SkillCategory::from('frameworks'))->toBe(SkillCategory::Frameworks)
        ->and(SkillCategory::from('databases'))->toBe(SkillCategory::Databases)
        ->and(SkillCategory::from('devops'))->toBe(SkillCategory::Devops)
        ->and(SkillCategory::from('platform'))->toBe(SkillCategory::Platform)
        ->and(SkillCategory::from('security'))->toBe(SkillCategory::Security);
});

test('enum from throws for invalid value', function () {
    SkillCategory::from('invalid');
})->throws(ValueError::class);

test('enum tryFrom returns null for invalid value', function () {
    expect(SkillCategory::tryFrom('invalid'))->toBeNull()
        ->and(SkillCategory::tryFrom('Languages'))->toBeNull()
        ->and(SkillCategory::tryFrom(''))->toBeNull();
});

test('enum cases match expected names', function () {
    expect(SkillCategory::Languages->name)->toBe('Languages')
        ->and(SkillCategory::Frameworks->name)->toBe('Frameworks')
        ->and(SkillCategory::Databases->name)->toBe('Databases')
        ->and(SkillCategory::Devops->name)->toBe('Devops')
        ->and(SkillCategory::Platform->name)->toBe('Platform')
        ->and(SkillCategory::Security->name)->toBe('Security');
});
