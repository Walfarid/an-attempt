<?php

namespace Database\Factories;

use App\Models\Publication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publication>
 */
class PublicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $authors = fake()->name().' et al.';

        return [
            'citation' => $authors.' "'.fake()->sentence(6).'," '.fake()->year().'.',
            'venue' => fake()->randomElement([
                'Journal of Systems and Software',
                'IEEE Access',
                'ASE Conference Proceedings',
            ]),
            'year' => fake()->numberBetween(2018, 2025),
            'doi_url' => 'https://doi.org/'.fake()->bothify('10.####/#####.###'),
        ];
    }
}
