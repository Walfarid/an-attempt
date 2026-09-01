<?php

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $path
 * @property string $mime
 * @property int $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $url
 */
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'path',
        'mime',
        'size',
    ];

    /**
     * The public URL of the media file when the media disk is configured.
     * Null otherwise so pages degrade gracefully.
     *
     * @return Attribute<string|null, never>
     */
    protected function url(): Attribute
    {
        return Attribute::make(get: function () {
            if (! config('filesystems.disks.media.bucket')) {
                return null;
            }

            return Storage::disk('media')->url($this->path);
        })->shouldCache();
    }
}
