<?php

namespace App\Models;

use App\Support\Markdown;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $excerpt
 * @property string $body
 * @property string|null $cover_image_path
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $teaser_text list teaser, set before serialization by controllers
 * @property string $body_html Markdown body rendered to HTML, set before serialization
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * Appended attributes for serialization.
     *
     * @var list<string>
     */
    protected $appends = ['cover_url'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'body',
        'cover_image_path',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * Posts that are live for the public site.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /**
     * The public URL of the cover image, when one is set and the media
     * disk is configured. Null otherwise so pages degrade gracefully.
     *
     * @return Attribute<string|null, never>
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->cover_image_path === null || ! config('filesystems.disks.media.bucket')) {
                return null;
            }

            return Storage::disk('media')->url($this->cover_image_path);
        });
    }

    /**
     * The body rendered from Markdown to HTML.
     */
    public function bodyHtml(): string
    {
        return Markdown::toHtml($this->body);
    }

    /**
     * The list teaser: the excerpt when set, otherwise a plain-text
     * summary stripped of common Markdown syntax (avoids a full render).
     */
    public function teaser(int $words = 30): string
    {
        if ($this->excerpt !== null && $this->excerpt !== '') {
            return $this->excerpt;
        }

        $text = (string) preg_replace([
            '/^#{1,6}\s+/',
            '/[*_`]{1,3}/',
            '/!\[.*?\]\(.+?\)/',
            '/\[(.+?)\]\(.+?\)/',
        ], ['$1', '', '', ''], $this->body);

        return Str::words(trim($text), $words);
    }
}
