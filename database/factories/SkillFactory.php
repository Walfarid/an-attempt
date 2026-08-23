<?php

namespace Database\Factories;

use App\Enums\SkillCategory;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Go', 'PHP', 'Laravel', 'Vue', 'Nuxt', 'PostgreSQL',
                'Docker', 'Kubernetes', 'Terraform', 'Redis',
            ]),
            'category' => fake()->randomElement(SkillCategory::class),
        ];
    }
}
