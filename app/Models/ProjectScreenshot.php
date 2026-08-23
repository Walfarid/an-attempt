<?php

namespace App\Models;

use Database\Factories\ProjectScreenshotFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $project_id
 * @property string $path
 * @property string|null $alt
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProjectScreenshot extends Model
{
    /** @use HasFactory<ProjectScreenshotFactory> */
    use HasFactory;

    /**
     * Appended attributes for serialization.
     *
     * @var list<string>
     */
    protected $appends = ['url'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'path',
        'alt',
        'sort_order',
    ];

    /**
     * The public URL of the stored screenshot. Null when the media
     * disk is not configured, so pages degrade gracefully instead of
     * failing on serialization.
     *
     * @return Attribute<string|null, never>
     */
    protected function url(): Attribute
    {
        return Attribute::make(get: fn () => config('filesystems.disks.media.bucket')
            ? Storage::disk('media')->url($this->path)
            : null);
    }

    /**
     * The project this screenshot belongs to.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
