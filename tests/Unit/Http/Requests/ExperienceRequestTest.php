<?php

use App\Http\Requests\Dashboard\ExperienceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// Rules structure tests

test('rules returns the expected validation structure', function () {
    $request = new ExperienceRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKeys(['role', 'company', 'location', 'started_at', 'ended_at', 'summary', 'highlights', 'highlights.*'])
        ->and($rules['role'])->toContain('required', 'string', 'max:255')
        ->and($rules['company'])->toContain('required', 'string', 'max:255')
        ->and($rules['location'])->toContain('nullable', 'string', 'max:255')
        ->and($rules['started_at'])->toContain('required', 'date')
        ->and($rules['ended_at'])->toContain('nullable', 'date', 'after_or_equal:started_at')
        ->and($rules['summary'])->toContain('required', 'string')
        ->and($rules['highlights'])->toContain('nullable', 'array');
});

// Role validation tests

test('role is required', function () {
    $validator = Validator::make(
        ['company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('role'))->toBeTrue();
});

test('role must be a string', function () {
    $validator = Validator::make(
        ['role' => ['not', 'a', 'string'], 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('role'))->toBeTrue();
});

test('role has a maximum length of 255 characters', function () {
    $rules = (new ExperienceRequest)->rules();

    $tooLong = Validator::make(
        ['role' => str_repeat('a', 256), 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        $rules
    );

    $justRight = Validator::make(
        ['role' => str_repeat('a', 255), 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        $rules
    );

    expect($tooLong->fails())->toBeTrue()
        ->and($tooLong->errors()->has('role'))->toBeTrue()
        ->and($justRight->fails())->toBeFalse();
});

test('role can contain unicode characters', function () {
    $validator = Validator::make(
        ['role' => 'Senior Développeur 日本語', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

// Company validation tests

test('company is required', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('company'))->toBeTrue();
});

test('company must be a string', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 12345, 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('company'))->toBeTrue();
});

test('company has a maximum length of 255 characters', function () {
    $rules = (new ExperienceRequest)->rules();

    $tooLong = Validator::make(
        ['role' => 'Software Engineer', 'company' => str_repeat('a', 256), 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        $rules
    );

    $justRight = Validator::make(
        ['role' => 'Software Engineer', 'company' => str_repeat('a', 255), 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        $rules
    );

    expect($tooLong->fails())->toBeTrue()
        ->and($tooLong->errors()->has('company'))->toBeTrue()
        ->and($justRight->fails())->toBeFalse();
});

// Location validation tests

test('location is optional', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('location can be null', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'location' => null, 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('location must be a string when provided', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'location' => ['array'], 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('location'))->toBeTrue();
});

test('location has a maximum length of 255 characters', function () {
    $rules = (new ExperienceRequest)->rules();

    $tooLong = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'location' => str_repeat('a', 256), 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        $rules
    );

    $justRight = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'location' => str_repeat('a', 255), 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        $rules
    );

    expect($tooLong->fails())->toBeTrue()
        ->and($tooLong->errors()->has('location'))->toBeTrue()
        ->and($justRight->fails())->toBeFalse();
});

// Started_at validation tests

test('started_at is required', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('started_at'))->toBeTrue();
});

test('started_at must be a valid date', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => 'not-a-date', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('started_at'))->toBeTrue();
});

test('started_at accepts valid date formats', function (string $date) {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => $date, 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
})->with([
    'Y-m-d' => '2020-01-15',
    'Y-m-d H:i:s' => '2020-01-15 10:30:00',
    'ISO 8601' => '2020-01-15T10:30:00Z',
    'future date' => '2099-12-31',
]);

// Ended_at validation tests

test('ended_at is optional', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('ended_at can be null', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'ended_at' => null, 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('ended_at must be a valid date when provided', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'ended_at' => 'invalid-date', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('ended_at'))->toBeTrue();
});

test('ended_at cannot precede started_at', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'ended_at' => '2019-01-01', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('ended_at'))->toBeTrue();
});

test('ended_at can be equal to started_at', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'ended_at' => '2020-01-01', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('ended_at can be after started_at', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'ended_at' => '2023-06-30', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('ended_at accepts valid date formats', function (string $date) {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'ended_at' => $date, 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
})->with([
    'Y-m-d' => '2023-06-30',
    'Y-m-d H:i:s' => '2023-06-30 10:30:00',
    'ISO 8601' => '2023-06-30T00:00:00Z',
]);

// Summary validation tests

test('summary is required', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('summary'))->toBeTrue();
});

test('summary must be a string', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => ['not', 'a', 'string']],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('summary'))->toBeTrue();
});

test('summary can be a long text', function () {
    $longText = str_repeat('This is a paragraph about work experience. ', 50);

    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => $longText],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('summary can contain markdown syntax', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => "Led the **core platform** team.\n\n- Built APIs\n- Scaled infrastructure"],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

// Highlights validation tests

test('highlights is optional', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('highlights can be null', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary', 'highlights' => null],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('highlights must be an array when provided', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary', 'highlights' => 'not-an-array'],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('highlights'))->toBeTrue();
});

test('highlights can be an empty array', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary', 'highlights' => []],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('highlights array items must be strings', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary', 'highlights' => ['Valid string', 123, ['nested']]],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('highlights.1'))->toBeTrue()
        ->and($validator->errors()->has('highlights.2'))->toBeTrue();
});

test('highlights array items cannot be empty', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary', 'highlights' => ['First', '', 'Third']],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('highlights.1'))->toBeTrue();
});

test('highlights accepts valid string array', function () {
    $validator = Validator::make(
        ['role' => 'Software Engineer', 'company' => 'Tech Corp', 'started_at' => '2020-01-01', 'summary' => 'Work summary', 'highlights' => ['Built REST APIs', 'Led a team of 5', 'Improved performance by 40%']],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

// Complete validation tests

test('validation passes with minimal valid data', function () {
    $validator = Validator::make(
        [
            'role' => 'Software Engineer',
            'company' => 'Tech Corp',
            'started_at' => '2020-01-01',
            'summary' => 'Led development efforts.',
        ],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('validation passes with all fields provided', function () {
    $validator = Validator::make(
        [
            'role' => 'Senior Software Engineer',
            'company' => 'Tech Corp International',
            'location' => 'Berlin, Germany',
            'started_at' => '2020-01-15',
            'ended_at' => '2023-06-30',
            'summary' => 'Led development of core platform and mentored junior engineers.',
            'highlights' => ['Scaled infrastructure to 10M users', 'Built microservices architecture', 'Led a team of 5'],
        ],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('validation passes for current position without ended_at', function () {
    $validator = Validator::make(
        [
            'role' => 'Staff Engineer',
            'company' => 'Current Company',
            'location' => 'Remote',
            'started_at' => '2023-01-01',
            'summary' => 'Currently leading platform architecture.',
            'highlights' => ['Designing next-gen platform'],
        ],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('all invalid fields are reported simultaneously', function () {
    $validator = Validator::make(
        [
            'role' => str_repeat('a', 256),
            'company' => ['not', 'a', 'string'],
            'location' => str_repeat('a', 256),
            'started_at' => 'not-a-date',
            'ended_at' => 'also-not-a-date',
            'summary' => 12345,
            'highlights' => 'not-an-array',
        ],
        (new ExperienceRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain('role', 'company', 'location', 'started_at', 'ended_at', 'summary', 'highlights');
});

test('authorization is handled via route middleware', function () {
    $request = new ExperienceRequest;

    expect(method_exists($request, 'authorize'))->toBeFalse();
});
