<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 */
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * The published posts carrying this tag.
     *
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * Tags that at least one published post uses.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUsed(Builder $query): Builder
    {
        $publishedIds = Post::query()
            ->published()
            ->select('posts.id')
            ->toBase();

        return $query->whereIn('id', function ($sub) use ($publishedIds): void {
            $sub->select('post_tag.tag_id')
                ->from('post_tag')
                ->whereIn('post_tag.post_id', $publishedIds);
        });
    }
}
