<?php

/** One-off generator for public/og-default.png (1200x630 social card). */
$root = dirname(__DIR__);
$w = 1200;
$h = 630;

$im = imagecreatetruecolor($w, $h);

// Light-mode brand: paper #fafaf5, ink #1a1a18, accent #17594a.
$paper = (int) imagecolorallocate($im, 0xFA, 0xFA, 0xF5);
$ink = (int) imagecolorallocate($im, 0x1A, 0x1A, 0x18);
$accent = (int) imagecolorallocate($im, 0x17, 0x59, 0x4A);
$rule = (int) imagecolorallocate($im, 0xE3, 0xE2, 0xDC);

imagefill($im, 0, 0, $paper);

// Left accent bar.
imagefilledrectangle($im, 0, 0, 16, $h, $accent);

// Hairline frame.
imagerectangle($im, 0, 0, $w - 1, $h - 1, $rule);

$font = $root.'/vendor/laravel/framework/resources/fonts/DejaVuSans-Bold.ttf';

$text = function (int $size, string $s, int $x, int $y, int $color) use ($im, $font): void {
    if (is_file($font)) {
        imagettftext($im, $size, 0, $x, $y, $color, $font, $s);

        return;
    }

    // Fallback: built-in bitmap font (no TTF available).
    imagestring($im, 5, $x, $y - 40, $s, $color);
};

$text(64, 'Walfa', 80, 250, $ink);
$text(30, 'Software developer — Laravel, APIs, deployment platforms', 80, 330, $accent);

imagepng($im, $root.'/public/og-default.png');

echo is_file($root.'/public/og-default.png') ? "written\n" : "FAILED\n";
