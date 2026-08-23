<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'headline' => fake()->jobTitle(),
            'bio' => fake()->paragraphs(2, true),
            'location' => fake()->city(),
            'github_url' => 'https://github.com/'.$this->faker->userName(),
            'linkedin_url' => 'https://www.linkedin.com/in/'.$this->faker->userName(),
        ];
    }
}
