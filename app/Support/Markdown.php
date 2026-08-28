<?php

namespace App\Support;

use League\CommonMark\CommonMarkConverter;

class Markdown
{
    private static ?CommonMarkConverter $converter = null;

    /** @var array<string, string> In-memory cache for rendered Markdown */
    private static array $cache = [];

    public static function toHtml(string $markdown): string
    {
        if (isset(self::$cache[$markdown])) {
            return self::$cache[$markdown];
        }

        if (self::$converter === null) {
            self::$converter = new CommonMarkConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 20,
            ]);
        }

        $html = (string) self::$converter->convert($markdown);
        self::$cache[$markdown] = $html;

        return $html;
    }
}
