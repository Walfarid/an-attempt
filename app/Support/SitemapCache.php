<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Centralises the cache keys used by the public sitemap and the dashboard
 * controllers that invalidate it after content changes.
 */
class SitemapCache
{
    public const XML = 'sitemap.xml';

    public const LAST_MODIFIED = 'sitemap.last_modified';

    /** Flush every sitemap-related cache entry. */
    public static function invalidate(): void
    {
        Cache::forget(self::XML);
        Cache::forget(self::LAST_MODIFIED);
    }
}
