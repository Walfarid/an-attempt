<?php

namespace Database\Factories;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-10 years', '-1 year');

        return [
            'role' => fake()->randomElement([
                'Software Developer', 'Backend Engineer', 'Full-stack Developer',
            ]),
            'company' => fake()->company(),
            'location' => fake()->city().', '.fake()->country(),
            'started_at' => $start,
            'ended_at' => fake()->boolean(70)
                ? fake()->dateTimeBetween($start, 'now')
                : null,
            'summary' => fake()->paragraph(),
            'highlights' => [
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
            ],
        ];
    }

    /**
     * An experience with no end date — the current position.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => null,
        ]);
    }
}
