<?php

namespace App\Models;

use App\Models\Concerns\HasCoverImage;
use App\Support\Markdown;
use Database\Factories\GuideFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $body
 * @property string|null $cover_image_path
 * @property Carbon|null $published_at
 * @property string|null $teaser
 * @property string|null $prerequisites
 * @property string|null $estimated_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $body_html Markdown body rendered to HTML, set before serialization
 */
class Guide extends Model
{
    /** @use HasFactory<GuideFactory> */
    use HasCoverImage, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'body',
        'cover_image_path',
        'published_at',
        'teaser',
        'prerequisites',
        'estimated_time',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * Guides that are live for the public site.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /**
     * The posts associated with this guide.
     *
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * The body rendered from Markdown to HTML.
     */
    public function bodyHtml(): string
    {
        return Markdown::toHtml($this->body ?? '');
    }

    /**
     * The list teaser: the hand-written teaser when set, otherwise a
     * plain-text summary of the body stripped of common Markdown syntax.
     */
    public function teaser(int $words = 30): string
    {
        if ($this->teaser !== null && $this->teaser !== '') {
            return $this->teaser;
        }

        if ($this->body === null || $this->body === '') {
            return '';
        }

        $text = (string) preg_replace(
            ['/^#{1,6}\s+/', '/[*_`]{1,3}/', '/!\[.*?\]\(.+?\)/', '/\[(.+?)\]\(.+?\)/'],
            ['$1', '', '', ''],
            $this->body,
        );

        return Str::words(trim($text), $words);
    }
}
