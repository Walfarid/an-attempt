<?php

namespace App\Support;

use App\Support\Markdown\DiagramInlineParser;
use App\Support\Markdown\DiagramNode;
use App\Support\Markdown\DiagramRenderer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

class Markdown
{
    private static ?MarkdownConverter $converter = null;

    /** @var array<string, string> In-memory cache for rendered Markdown */
    private static array $cache = [];

    /** Maximum cache entries to prevent unbounded memory growth in long-running processes. */
    private const MAX_CACHE_SIZE = 256;

    public static function toHtml(string $markdown): string
    {
        if (isset(self::$cache[$markdown])) {
            return self::$cache[$markdown];
        }

        if (self::$converter === null) {
            $environment = new Environment([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 20,
            ]);

            $environment->addExtension(new CommonMarkCoreExtension);
            $environment->addInlineParser(new DiagramInlineParser);
            $environment->addRenderer(DiagramNode::class, new DiagramRenderer);

            self::$converter = new MarkdownConverter($environment);
        }

        $html = (string) self::$converter->convert($markdown);

        // Evict oldest half when the cache is full (simple FIFO eviction).
        if (count(self::$cache) >= self::MAX_CACHE_SIZE) {
            self::$cache = array_slice(self::$cache, (int) (self::MAX_CACHE_SIZE / 2), null, true);
        }

        self::$cache[$markdown] = $html;

        return $html;
    }
}
