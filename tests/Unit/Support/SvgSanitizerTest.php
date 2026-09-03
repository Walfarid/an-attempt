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

test('returns empty string for malformed xml without throwing', function () {
    $svg = '<svg><rect></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->toBe('');
});

test('returns empty string for unclosed svg without throwing', function () {
    $svg = '<svg><rect/></svg';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->toBe('');
});

test('returns empty string for input without root svg element', function () {
    $svg = '<rect width="10" height="10"/>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->toBe('');
});

test('strips js scheme from href attributes', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><a href="js:alert(1)">link</a></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('js:');
});

test('strips js scheme from xlink:href attributes', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="js:alert(1)"/></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('js:');
});

test('strips mixed-case javascript scheme from href', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><a href="JAVASCRIPT:alert(1)">link</a></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('JAVASCRIPT:')
        ->and($result)->not->toContain('javascript:');
});

test('strips mixed-case javascript scheme from xlink:href', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="JaVaScRiPt:alert(1)"/></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('JaVaScRiPt:')
        ->and($result)->not->toContain('javascript:');
});

test('regression strips data scheme from href', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><a href="data:text/html,<script>alert(1)</script>">link</a></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('data:');
});

test('regression strips vbscript scheme from href', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><a href="vbscript:MsgBox(1)">link</a></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('vbscript:');
});

test('removes external file references from use href', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><use href="/assets/icon.svg#id"/></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('/assets/icon.svg#id');
});

test('preserves internal fragment references in use href', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><defs><circle id="dot"/></defs><use href="#dot"/></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->toContain('href="#dot"');
});

test('removes external file references from use xlink:href', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="/assets/icon.svg#id"/></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->not->toContain('/assets/icon.svg#id');
});

test('rejects doctype with external entity declarations', function () {
    $svg = '<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg"><rect width="10"/></svg>';
    $result = SvgSanitizer::sanitize($svg);

    expect($result)->toBe('');
});

test('restores libxml internal errors state after sanitization', function () {
    $previousState = libxml_use_internal_errors(false);

    SvgSanitizer::sanitize('<svg><rect/></svg>');
    $stateAfterFirst = libxml_use_internal_errors($previousState);

    SvgSanitizer::sanitize('<svg><rect></svg>');
    $stateAfterSecond = libxml_use_internal_errors($previousState);

    expect($stateAfterFirst)->toBeFalse()
        ->and($stateAfterSecond)->toBeFalse();

    libxml_use_internal_errors($previousState);
});
