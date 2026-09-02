<?php

namespace Database\Factories;

use App\Models\Guide;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Guide>
 */
class GuideFactory extends Factory
{
    /**
     * Slugs handed out by this factory instance, so a single
     * count(N)->create() batch cannot collide with itself.
     *
     * @var array<string, true>
     */
    protected array $usedSlugs = [];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = collect([
            'How to Install Apache on Ubuntu',
            'Setting Up Docker for Laravel',
            'Deploy a VPS with Caddy',
            'Hardening SSH on a New Server',
            'Backing Up PostgreSQL Automatically',
        ])->random();

        $estimated_time = collect(['10 minutes', '15 minutes', '30 minutes', '1 hour'])->random();

        return [
            'title' => $title,
            'body' => "## Step 1: Prepare the environment\n\n".collect(range(1, 3))->map(fn () => fake()->sentence())->implode("\n\n")."\n\n## Step 2: Install\n\n```bash\n".fake()->sentence()."\n```\n\n## Step 3: Verify\n\n".fake()->sentence(),
            'cover_image_path' => fake()->boolean(40) ? 'guides/'.fake()->uuid().'.png' : null,
            'published_at' => now(),
            'teaser' => fake()->sentence(12),
            'prerequisites' => fake()->boolean(70) ? 'A Linux server with SSH access and sudo privileges' : null,
            'estimated_time' => $estimated_time,
        ];
    }

    /**
     * Derive the slug from the final title unless one was given
     * explicitly — the definition's title may be overridden. The
     * title pool is small, so guard against collisions under the
     * unique guides.slug index: instance memory catches siblings
     * made before their batch is saved, the DB query catches rows
     * from earlier runs.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Guide $guide) {
            $base = Str::slug((string) $guide->title);
            $slug = $guide->slug ?? $base;

            while (isset($this->usedSlugs[$slug]) || Guide::where('slug', $slug)->exists()) {
                $slug = $base.'-'.Str::lower(Str::random(6));
            }

            $this->usedSlugs[$slug] = true;
            $guide->slug = $slug;
        });
    }

    /**
     * A draft guide — hidden from the public site.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }
}
