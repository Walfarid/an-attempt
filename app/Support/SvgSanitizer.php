<?php

namespace App\Support;

/**
 * Sanitizes inline SVG content to prevent XSS attacks.
 *
 * Uses DOMDocument to parse SVG and strips dangerous elements, attributes,
 * and protocol-based URI references.
 */
class SvgSanitizer
{
    /** @var array<string, list<string>> Allowed SVG elements mapped to their allowed attributes. */
    private const ALLOWED = [
        'svg' => ['width', 'height', 'viewbox', 'xmlns', 'class', 'fill', 'stroke', 'stroke-width', 'opacity', 'transform', 'aria-hidden', 'aria-label', 'role', 'style', 'x', 'y'],
        'path' => ['d', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset', 'opacity', 'transform', 'class', 'style'],
        'circle' => ['cx', 'cy', 'r', 'fill', 'stroke', 'stroke-width', 'opacity', 'transform', 'class', 'style'],
        'rect' => ['x', 'y', 'width', 'height', 'rx', 'ry', 'fill', 'stroke', 'stroke-width', 'opacity', 'transform', 'class', 'style'],
        'ellipse' => ['cx', 'cy', 'rx', 'ry', 'fill', 'stroke', 'stroke-width', 'opacity', 'transform', 'class', 'style'],
        'line' => ['x1', 'y1', 'x2', 'y2', 'stroke', 'stroke-width', 'opacity', 'transform', 'class', 'style'],
        'polygon' => ['points', 'fill', 'stroke', 'stroke-width', 'opacity', 'transform', 'class', 'style'],
        'polyline' => ['points', 'fill', 'stroke', 'stroke-width', 'opacity', 'transform', 'class', 'style'],
        'g' => ['fill', 'stroke', 'stroke-width', 'opacity', 'transform', 'class', 'style'],
        'defs' => [],
        'clippath' => ['id', 'clippathunits'],
        'lineargradient' => ['id', 'x1', 'y1', 'x2', 'y2', 'gradientunits', 'gradienttransform'],
        'radialgradient' => ['id', 'cx', 'cy', 'r', 'fx', 'fy', 'gradientunits', 'gradienttransform'],
        'stop' => ['offset', 'stop-color', 'stop-opacity'],
        'symbol' => ['id', 'viewbox'],
        'use' => ['href', 'xlink:href', 'x', 'y', 'width', 'height', 'transform'],
        'text' => ['x', 'y', 'dx', 'dy', 'fill', 'stroke', 'font-family', 'font-size', 'text-anchor', 'class', 'style'],
        'tspan' => ['x', 'y', 'dx', 'dy', 'fill', 'font-family', 'font-size', 'class', 'style'],
        'title' => [],
        'desc' => [],
        'metadata' => [],
        'marker' => ['id', 'viewbox', 'refx', 'refy', 'markerwidth', 'markerheight', 'orient'],
    ];

    /** @var list<string> Dangerous elements that are always removed entirely. */
    private const BLOCKED_ELEMENTS = [
        'script',
        'foreignobject',
        'animate',
        'set',
    ];

    /**
     * Sanitize an SVG HTML string, removing dangerous content.
     */
    public static function sanitize(string $svg): string
    {
        if (trim($svg) === '') {
            return '';
        }

        if (preg_match('/<!DOCTYPE[^>]*\[.*<!ENTITY[^>]*SYSTEM/i', $svg)) {
            return '';
        }

        $internalErrors = libxml_use_internal_errors(true);

        $dom = new \DOMDocument;
        $loaded = $dom->loadXML($svg, LIBXML_NONET);

        if (! $loaded || $dom->documentElement === null) {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);

            return '';
        }

        if (strtolower($dom->documentElement->tagName) !== 'svg') {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);

            return '';
        }

        self::walkNodes($dom->documentElement);

        $result = $dom->saveHTML($dom->documentElement) ?: '';

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $result;
    }

    private static function walkNodes(?\DOMNode $node): void
    {
        if ($node === null) {
            return;
        }

        $remove = [];

        foreach ($node->childNodes as $child) {
            if (! $child instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::BLOCKED_ELEMENTS, true)) {
                $remove[] = $child;

                continue;
            }

            if (! isset(self::ALLOWED[$tag])) {
                $remove[] = $child;

                continue;
            }

            self::sanitizeAttributes($child, $tag);
            self::walkNodes($child);
        }

        foreach ($remove as $child) {
            $node->removeChild($child);
        }
    }

    private static function sanitizeAttributes(\DOMElement $element, string $tag): void
    {
        $allowed = self::ALLOWED[$tag] ?? [];
        $removeAttrs = [];

        foreach ($element->attributes as $attr) {
            $name = strtolower($attr->nodeName);

            if (str_starts_with($name, 'on')) {
                $removeAttrs[] = $attr->nodeName;

                continue;
            }

            if (! in_array($name, $allowed, true)) {
                $removeAttrs[] = $attr->nodeName;

                continue;
            }

            $value = strtolower(trim($attr->nodeValue ?? ''));

            if (str_starts_with($value, 'javascript:') || str_starts_with($value, 'data:') || str_starts_with($value, 'vbscript:') || str_starts_with($value, 'js:')) {
                $removeAttrs[] = $attr->nodeName;

                continue;
            }

            if (in_array($name, ['href', 'xlink:href']) && preg_match('/^\s*(javascript|data|vbscript|js):/i', $attr->nodeValue ?? '', $matches)) {
                $removeAttrs[] = $attr->nodeName;

                continue;
            }

            if ($tag === 'use' && in_array($name, ['href', 'xlink:href'], true)) {
                $raw = trim($attr->nodeValue ?? '');
                if ($raw !== '' && ! str_starts_with($raw, '#')) {
                    $removeAttrs[] = $attr->nodeName;
                }
            }
        }

        foreach ($removeAttrs as $name) {
            $element->removeAttribute($name);
        }
    }
}
