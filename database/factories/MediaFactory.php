<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Paths handed out by this factory instance, so a single
     * count(N)->create() batch cannot collide with itself.
     *
     * @var array<string, true>
     */
    protected array $usedPaths = [];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'path' => 'uploads/fake-'.uniqid().'.png',
            'mime' => 'image/png',
            'size' => fake()->numberBetween(1000, 5000000),
        ];
    }

    /**
     * Ensure the path is unique: instance memory catches siblings
     * made before their batch is saved, the DB query catches rows
     * from earlier runs.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Media $media) {
            $base = $media->path ?? 'uploads/fake-'.uniqid().'.png';
            $path = $base;

            while (isset($this->usedPaths[$path]) || Media::where('path', $path)->exists()) {
                $path = 'uploads/fake-'.Str::lower(Str::random(12)).'.png';
            }

            $this->usedPaths[$path] = true;
            $media->path = $path;
        });
    }
}
