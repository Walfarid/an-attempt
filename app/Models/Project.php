<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'description',
        'year',
        'live_url',
        'repo_url',
        'image_tone',
        'featured',
        'sort_order',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Projects that are live for the public site.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /**
     * The skills showcased by this project.
     *
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    /**
     * The screenshots attached to this project, in display order.
     *
     * @return HasMany<ProjectScreenshot, $this>
     */
    public function screenshots(): HasMany
    {
        return $this->hasMany(ProjectScreenshot::class)->orderBy('sort_order');
    }
}
