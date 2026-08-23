<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
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
            'Why I keep coming back to Laravel',
            'Shipping a portfolio with Inertia',
            'Notes on running Garage object storage',
            'What clean architecture means in practice',
            'Debugging MariaDB collation mismatches',
        ])->random();

        return [
            'title' => $title,
            'excerpt' => fake()->boolean(70) ? fake()->sentence(12) : null,
            'body' => '## '.fake()->sentence()."\n\n".collect(range(1, 3))->map(fn () => fake()->sentence())->implode("\n\n"),
            'cover_image_path' => fake()->boolean(40) ? 'posts/'.fake()->uuid().'.png' : null,
            'published_at' => now(),
        ];
    }

    /**
     * Derive the slug from the final title unless one was given
     * explicitly — the definition's title may be overridden. The
     * title pool is small, so guard against collisions under the
     * unique posts.slug index: instance memory catches siblings
     * made before their batch is saved, the DB query catches rows
     * from earlier runs.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Post $post) {
            $base = Str::slug((string) $post->title);
            $slug = $post->slug ?? $base;

            while (isset($this->usedSlugs[$slug]) || Post::where('slug', $slug)->exists()) {
                $slug = $base.'-'.Str::lower(Str::random(6));
            }

            $this->usedSlugs[$slug] = true;
            $post->slug = $slug;
        });
    }

    /**
     * A draft post — hidden from the public site.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }
}
