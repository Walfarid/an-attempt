<?php

namespace App\Models;

use Database\Factories\ProjectScreenshotFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn () => config('filesystems.disks.media.bucket')
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
