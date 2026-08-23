<?php

namespace App\Models;

use App\Support\Markdown;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $headline
 * @property string $bio
 * @property string|null $location
 * @property string|null $github_url
 * @property string|null $linkedin_url
 * @property string|null $avatar_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $bio_html Markdown bio rendered to HTML, set before serialization
 */
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'headline',
        'bio',
        'location',
        'github_url',
        'linkedin_url',
        'avatar_path',
    ];

    /**
     * The singleton portfolio profile. There is always exactly one row;
     * the seeder creates it and the dashboard edits it.
     */
    public static function current(): self
    {
        return static::query()->firstOrFail();
    }

    /**
     * The bio rendered from Markdown to HTML.
     */
    public function bioHtml(): string
    {
        return Markdown::toHtml($this->bio);
    }
}
