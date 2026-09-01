<?php

namespace App\Support;

use League\CommonMark\GithubFlavoredMarkdownConverter;

class Markdown
{
    private static ?GithubFlavoredMarkdownConverter $converter = null;

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
            self::$converter = new GithubFlavoredMarkdownConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 20,
            ]);
        }

        $svgBlocks = [];
        $markdown = self::extractSvgBlocks($markdown, $svgBlocks);

        $html = (string) self::$converter->convert(self::preprocess($markdown));

        $html = self::restoreSvgBlocks($html, $svgBlocks);

        // Evict oldest half when the cache is full (simple FIFO eviction).
        if (count(self::$cache) >= self::MAX_CACHE_SIZE) {
            self::$cache = array_slice(self::$cache, (int) (self::MAX_CACHE_SIZE / 2), null, true);
        }

        self::$cache[$markdown] = $html;

        return $html;
    }

    /**
     * Extract inline <svg>…</svg> blocks from markdown before conversion.
     *
     * Replaces each block with a placeholder so CommonMark does not alter the SVG markup.
     *
     * @param  array<int, string>  $blocks  Populated by reference with extracted SVG strings.
     */
    private static function extractSvgBlocks(string $markdown, array &$blocks): string
    {
        return (string) preg_replace_callback(
            '/<svg\b[^>]*>.*?<\/svg>/si',
            function (array $match) use (&$blocks): string {
                $blocks[] = SvgSanitizer::sanitize($match[0]);

                return 'SVG_BLOCK_'.(count($blocks) - 1).'_END';
            },
            $markdown,
        );
    }

    /**
     * Replace SVG placeholders with the sanitized SVG blocks.
     *
     * @param  array<int, string>  $blocks
     */
    private static function restoreSvgBlocks(string $html, array $blocks): string
    {
        return (string) preg_replace_callback(
            '/SVG_BLOCK_(\d+)_END/',
            fn (array $match): string => $blocks[(int) $match[1]] ?? '',
            $html,
        );
    }

    /**
     * Clean up known malformed markdown patterns before conversion.
     *
     * Strips bold markers (**) from around heading lines and horizontal rules so that
     * "**## Heading**" renders as <h2> and "**---**" renders as <hr> instead of <strong>.
     */
    private static function preprocess(string $markdown): string
    {
        $markdown = (string) preg_replace(
            '/^\*\*(#{1,6}\s+.+?)\*\*$/m',
            '$1',
            $markdown,
        );

        return (string) preg_replace(
            '/^\*\*([-_*]{3,})\*\*$/m',
            '$1',
            $markdown,
        );
    }
}
