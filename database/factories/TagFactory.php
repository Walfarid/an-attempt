<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /**
     * Slugs handed out by this factory instance, so a single
     * count(N)->create() batch cannot collide with itself.
     *
     * @var array<string, true>
     */
    protected array $usedNames = [];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = collect([
            'Laravel',
            'PHP',
            'TypeScript',
            'Vue',
            'Docker',
            'Performance',
            'Testing',
            'Databases',
        ])->random();

        // The pool is small — dedupe within a factory instance so
        // count(N)->create() batches stay under the unique indexes.
        $n = 2;

        while (isset($this->usedNames[$name])) {
            $name .= ' '.Str::random(4);
            $n++;

            if ($n > 40) {
                $name = Str::random(12);
            }
        }

        $this->usedNames[$name] = true;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
