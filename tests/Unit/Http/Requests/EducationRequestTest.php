<?php

use App\Http\Requests\Dashboard\EducationRequest;
use App\Models\Education;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('rules returns the expected validation structure', function () {
    $request = new EducationRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKeys(['school', 'degree', 'started_at', 'ended_at', 'details', 'details.*'])
        ->and($rules['school'])->toContain('required', 'string')
        ->and($rules['degree'])->toContain('required', 'string')
        ->and($rules['started_at'])->toContain('nullable', 'date')
        ->and($rules['ended_at'])->toContain('nullable', 'date', 'after_or_equal:started_at')
        ->and($rules['details'])->toContain('nullable', 'array');
});

test('school is required', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'degree' => 'M.Tech in Software Engineering',
        'details' => [],
    ])->assertInvalid('school');
});

test('degree is required', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'National University of Singapore',
        'details' => [],
    ])->assertInvalid('degree');
});

test('school must be a string', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => ['not', 'a', 'string'],
        'degree' => 'M.Tech',
        'details' => [],
    ])->assertInvalid('school');
});

test('degree must be a string', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 12345,
        'details' => [],
    ])->assertInvalid('degree');
});

test('school has a maximum length of 255 characters', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => str_repeat('a', 256),
        'degree' => 'M.Tech',
        'details' => [],
    ])->assertInvalid('school');

    $this->post('/dashboard/educations', [
        'school' => str_repeat('a', 255),
        'degree' => 'M.Tech',
        'details' => [],
    ])->assertRedirect(route('dashboard.educations.index'));
});

test('degree has a maximum length of 255 characters', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => str_repeat('a', 256),
        'details' => [],
    ])->assertInvalid('degree');

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => str_repeat('a', 255),
        'details' => [],
    ])->assertRedirect(route('dashboard.educations.index'));
});

test('started_at must be a valid date', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'started_at' => 'not-a-date',
        'details' => [],
    ])->assertInvalid('started_at');
});

test('started_at can be omitted', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'details' => [],
    ])->assertRedirect(route('dashboard.educations.index'));
});

test('started_at accepts valid date formats', function (string $date) {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'started_at' => $date,
        'details' => [],
    ])->assertRedirect(route('dashboard.educations.index'));
})->with([
    'Y-m-d' => '2025-08-01',
    'Y-m-d H:i:s' => '2025-08-01 10:30:00',
    'ISO 8601' => '2025-08-01T00:00:00Z',
]);

test('ended_at must be a valid date', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'ended_at' => 'invalid-date',
        'details' => [],
    ])->assertInvalid('ended_at');
});

test('ended_at can be omitted', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'details' => [],
    ])->assertRedirect(route('dashboard.educations.index'));
});

test('ended_at cannot precede started_at', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'started_at' => '2025-08-01',
        'ended_at' => '2024-01-01',
        'details' => [],
    ])->assertInvalid('ended_at');
});

test('ended_at can be equal to started_at', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'started_at' => '2025-08-01',
        'ended_at' => '2025-08-01',
        'details' => [],
    ])->assertRedirect(route('dashboard.educations.index'));
});

test('ended_at can be after started_at', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'started_at' => '2025-08-01',
        'ended_at' => '2026-06-30',
        'details' => [],
    ])->assertRedirect(route('dashboard.educations.index'));
});

test('ended_at validation passes when started_at is omitted', function () {
    $this->actingAs(User::factory()->create());

    // When started_at is not provided, after_or_equal:started_at is ignored
    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'ended_at' => '2025-08-01',
        'details' => [],
    ])->assertRedirect(route('dashboard.educations.index'));
});

test('details must be an array when provided', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'details' => 'not-an-array',
    ])->assertInvalid('details');
});

test('details can be an empty array', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'details' => [],
    ])->assertRedirect(route('dashboard.educations.index'));
});

test('details array items must be strings', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'details' => ['Valid string', 123, ['nested']],
    ]);

    // The validation errors are keyed as 'details.1' and 'details.2'
    $response->assertSessionHasErrors(['details.1', 'details.2']);
});

test('details array items cannot be empty', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'details' => ['First', '', 'Third'],
    ])->assertInvalid('details.1');
});

test('details accepts valid string array', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'details' => ['Focus on distributed systems', 'Research in machine learning'],
    ])->assertRedirect(route('dashboard.educations.index'));

    $education = Education::where('school', 'NUS')->first();
    expect($education->details)->toBe(['Focus on distributed systems', 'Research in machine learning']);
});

test('updating an education validates the same rules', function () {
    $education = Education::factory()->create();
    $this->actingAs(User::factory()->create());

    // Missing required fields should fail
    $this->put("/dashboard/educations/{$education->id}", [
        'started_at' => '2025-08-01',
        'details' => [],
    ])->assertInvalid(['school', 'degree']);

    // End date before start date should fail
    $this->put("/dashboard/educations/{$education->id}", [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'started_at' => '2025-08-01',
        'ended_at' => '2024-01-01',
        'details' => [],
    ])->assertInvalid('ended_at');

    // Valid data should pass
    $this->put("/dashboard/educations/{$education->id}", [
        'school' => 'Updated School',
        'degree' => 'Updated Degree',
        'details' => [],
    ])->assertRedirect(route('dashboard.educations.index'));

    expect($education->fresh()->school)->toBe('Updated School');
});

test('valid complete data passes validation', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'National University of Singapore',
        'degree' => 'M.Tech in Software Engineering',
        'started_at' => '2024-08-01',
        'ended_at' => '2026-06-30',
        'details' => ['Focus on software architecture', 'Thesis on distributed systems'],
    ])->assertRedirect(route('dashboard.educations.index'));

    $education = Education::where('school', 'National University of Singapore')->first();
    expect($education)->not->toBeNull()
        ->and($education->degree)->toBe('M.Tech in Software Engineering')
        ->and($education->started_at->format('Y-m-d'))->toBe('2024-08-01')
        ->and($education->ended_at->format('Y-m-d'))->toBe('2026-06-30')
        ->and($education->details)->toHaveCount(2);
});
