<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

trait HasCoverImage
{
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
        })->shouldCache();
    }
}
