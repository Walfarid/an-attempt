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
     * explicitly — the definition's title may be overridden.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Post $post) {
            $post->slug ??= Str::slug((string) $post->title);
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
