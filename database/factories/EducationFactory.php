<?php

namespace Database\Factories;

use App\Models\Education;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Education>
 */
class EducationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-12 years', '-4 years');
        $end = fake()->dateTimeBetween($start, '-1 year');

        return [
            'school' => fake()->randomElement([
                'National University of Singapore',
                'Institut Teknologi Bandung',
                'University of Melbourne',
            ]),
            'degree' => fake()->randomElement([
                'B.Sc. in Computer Science',
                'M.Tech in Software Engineering',
                'B.Eng. in Informatics',
            ]),
            'started_at' => $start,
            'ended_at' => $end,
            'details' => [
                fake()->sentence(),
                fake()->sentence(),
            ],
        ];
    }

    /**
     * An education record still in progress.
     */
    public function ongoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'ended_at' => null,
        ]);
    }
}
