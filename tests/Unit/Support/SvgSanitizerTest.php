<?php

use App\Support\SvgSanitizer;

test('preserves safe svg elements and attributes', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><rect x="1" y="1" width="22" height="22" fill="#ccc"/><path d="M12 2L2 22h20L12 2z" fill="none" stroke="#000"/></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->toContain('<svg')
        ->and($result)->toContain('viewBox="0 0 24 24"')
        ->and($result)->toContain('<rect')
        ->and($result)->toContain('<path')
        ->and($result)->toContain('d="M12 2L2 22h20L12 2z"');
});

test('removes script elements from svg', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/><script>alert("xss")</script></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('<script>')
        ->and($result)->not->toContain('alert')
        ->and($result)->toContain('<rect');
});

test('removes event handler attributes', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10" onclick="alert(1)" onload="alert(2)"/></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('onclick')
        ->and($result)->not->toContain('onload')
        ->and($result)->not->toContain('alert');
});

test('removes foreignobject elements', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="100" height="50"><body><iframe src="javascript:alert(1)"></iframe></body></foreignObject></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('<foreignObject')
        ->and($result)->not->toContain('<iframe')
        ->and($result)->not->toContain('javascript:');
});

test('removes unknown elements', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><unknown-el/><circle r="5"/></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('<unknown-el')
        ->and($result)->toContain('<circle');
});

test('removes unknown attributes from allowed elements', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" data-evil="x" class="ok"/></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('data-evil')
        ->and($result)->toContain('class="ok"');
});

test('strips javascript protocol from href attributes', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="javascript:alert(1)"/></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('javascript:');
});

test('removes animate elements', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"><animate attributeName="width" from="10" to="100"/></rect></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('<animate')
        ->and($result)->toContain('<rect');
});

test('returns empty string for empty input', function () {
    expect(SvgSanitizer::sanitize(''))->toBe('');
});
