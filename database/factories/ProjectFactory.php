<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->randomElement([
            'HR Platform · REST API & SSO',
            'Security Integration Platform',
            'Custom CMS · WYSIWYG Editor',
            'Threat Identification Automation',
            'Realtime Messaging Service',
            'IoT Monitoring Dashboard',
        ]);

        return [
            'slug' => str($title)->slug()->toString(),
            'title' => $title,
            'description' => fake()->sentence(12),
            'year' => (int) fake()->numberBetween(2018, 2026),
            'live_url' => fake()->boolean(50) ? 'https://example.com' : null,
            'repo_url' => fake()->boolean(70) ? 'https://github.com/example/repo' : null,
            'image_tone' => 'from-emerald-500/20 via-teal-500/10 to-transparent',
            'featured' => false,
            'sort_order' => 0,
            'published_at' => now(),
        ];
    }

    /**
     * A draft project — hidden from the public site.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }

    /**
     * A featured project.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
        ]);
    }
}
