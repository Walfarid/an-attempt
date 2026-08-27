<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectScreenshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectScreenshot>
 */
class ProjectScreenshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'path' => 'projects/'.fake()->uuid().'.png',
            'alt' => fake()->sentence(4),
            'sort_order' => 0,
        ];
    }
}
