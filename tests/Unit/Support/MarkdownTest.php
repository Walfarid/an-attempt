<?php

use App\Support\Markdown;

test('converts basic markdown to html', function () {
    expect(Markdown::toHtml('# Heading'))->toBe("<h1>Heading</h1>\n")
        ->and(Markdown::toHtml('**bold**'))->toBe("<p><strong>bold</strong></p>\n")
        ->and(Markdown::toHtml('*italic*'))->toBe("<p><em>italic</em></p>\n")
        ->and(Markdown::toHtml('`code`'))->toBe("<p><code>code</code></p>\n");
});

test('converts links to anchor tags', function () {
    $html = Markdown::toHtml('[Example](https://example.com)');

    expect($html)->toContain('<a href="https://example.com">Example</a>');
});

test('converts unordered lists', function () {
    $markdown = "- Item 1\n- Item 2\n- Item 3";
    $html = Markdown::toHtml($markdown);

    expect($html)->toContain('<ul>')
        ->and($html)->toContain('<li>Item 1</li>')
        ->and($html)->toContain('<li>Item 2</li>')
        ->and($html)->toContain('<li>Item 3</li>')
        ->and($html)->toContain('</ul>');
});

test('converts ordered lists', function () {
    $markdown = "1. First\n2. Second\n3. Third";
    $html = Markdown::toHtml($markdown);

    expect($html)->toContain('<ol>')
        ->and($html)->toContain('<li>First</li>')
        ->and($html)->toContain('<li>Second</li>')
        ->and($html)->toContain('<li>Third</li>')
        ->and($html)->toContain('</ol>');
});

test('converts code blocks', function () {
    $markdown = "```\ncode here\n```";
    $html = Markdown::toHtml($markdown);

    expect($html)->toContain('<pre><code>')
        ->and($html)->toContain('code here')
        ->and($html)->toContain('</code></pre>');
});

test('converts blockquotes', function () {
    $html = Markdown::toHtml('> A quote');

    expect($html)->toContain('<blockquote>')
        ->and($html)->toContain('A quote')
        ->and($html)->toContain('</blockquote>');
});

test('converts horizontal rules', function () {
    expect(Markdown::toHtml('---'))->toContain('<hr')
        ->and(Markdown::toHtml('***'))->toContain('<hr')
        ->and(Markdown::toHtml('___'))->toContain('<hr');
});

test('converts paragraphs', function () {
    $html = Markdown::toHtml("Line one\n\nLine two");

    expect($html)->toContain('<p>Line one</p>')
        ->and($html)->toContain('<p>Line two</p>');
});

test('converts inline code within text', function () {
    $html = Markdown::toHtml('Use the `printf` function');

    expect($html)->toContain('<code>printf</code>');
});

test('converts nested inline elements', function () {
    $html = Markdown::toHtml('**bold and *italic***');

    expect($html)->toContain('<strong>')
        ->and($html)->toContain('<em>')
        ->and($html)->toContain('italic')
        ->and($html)->toContain('</em>')
        ->and($html)->toContain('</strong>');
});

test('handles empty input', function () {
    expect(Markdown::toHtml(''))->toBe('');
});

test('handles plain text without markdown syntax', function () {
    $html = Markdown::toHtml('Just plain text');

    expect($html)->toBe("<p>Just plain text</p>\n");
});

test('strips raw html from input', function () {
    $html = Markdown::toHtml('<script>alert("xss")</script>Hello');

    expect($html)->not->toContain('<script>')
        ->and($html)->not->toContain('alert');
});

test('strips html tags within markdown', function () {
    $html = Markdown::toHtml('<div class="evil">content</div>');

    expect($html)->not->toContain('<div>')
        ->and($html)->not->toContain('class="evil"');
});

test('removes javascript protocol from links', function () {
    $html = Markdown::toHtml('[Click me](javascript:alert(1))');

    expect($html)->not->toContain('javascript:')
        ->and($html)->not->toContain('alert');
});

test('removes data urls from links', function () {
    $html = Markdown::toHtml('[Link](data:text/html,<script>alert(1)</script>)');

    expect($html)->not->toContain('data:')
        ->and($html)->not->toContain('script');
});

test('preserves safe http and https links', function () {
    expect(Markdown::toHtml('[HTTP](http://example.com)'))->toContain('href="http://example.com"')
        ->and(Markdown::toHtml('[HTTPS](https://example.com)'))->toContain('href="https://example.com"');
});

test('handles deeply nested structures within limit', function () {
    $markdown = '> > > > > Deep nesting';
    $html = Markdown::toHtml($markdown);

    expect($html)->toContain('Deep nesting');
});

test('handles special characters', function () {
    $html = Markdown::toHtml('5 < 10 && 10 > 5');

    expect($html)->toContain('&lt;')
        ->and($html)->toContain('&gt;')
        ->and($html)->toContain('&amp;');
});

test('handles markdown with ampersands', function () {
    $html = Markdown::toHtml('Tom & Jerry');

    expect($html)->toContain('&amp;');
});

test('converts image syntax', function () {
    $html = Markdown::toHtml('![Alt text](https://example.com/image.png)');

    expect($html)->toContain('<img')
        ->and($html)->toContain('src="https://example.com/image.png"')
        ->and($html)->toContain('alt="Alt text"');
});

test('removes unsafe protocols from images', function () {
    $html = Markdown::toHtml('![Alt](javascript:alert(1))');

    expect($html)->not->toContain('javascript:');
});

test('handles mixed markdown elements', function () {
    $markdown = "# Title\n\nA paragraph with **bold** and [a link](https://example.com).\n\n- List item";
    $html = Markdown::toHtml($markdown);

    expect($html)->toContain('<h1>Title</h1>')
        ->and($html)->toContain('<strong>bold</strong>')
        ->and($html)->toContain('<a href="https://example.com">a link</a>')
        ->and($html)->toContain('<li>List item</li>');
});

test('handles fenced code blocks with language', function () {
    $markdown = "```php\necho 'Hello';\n```";
    $html = Markdown::toHtml($markdown);

    expect($html)->toContain('<pre><code')
        ->and($html)->toContain("echo 'Hello';");
});

test('handles multiple paragraphs correctly', function () {
    $markdown = "First paragraph.\n\nSecond paragraph.\n\nThird paragraph.";
    $html = Markdown::toHtml($markdown);

    expect($html)->toContain('<p>First paragraph.</p>')
        ->and($html)->toContain('<p>Second paragraph.</p>')
        ->and($html)->toContain('<p>Third paragraph.</p>');
});

test('handles line breaks within paragraphs', function () {
    $html = Markdown::toHtml("Line one\nLine two");

    expect($html)->toContain('Line one')
        ->and($html)->toContain('Line two');
});

test('handles setext-style headings', function () {
    $html = Markdown::toHtml("Heading\n======");

    expect($html)->toContain('<h1>Heading</h1>');
});

test('converts automatic links', function () {
    $html = Markdown::toHtml('<https://example.com>');

    expect($html)->toContain('<a href="https://example.com"');
});

test('converts automatic email links', function () {
    $html = Markdown::toHtml('<test@example.com>');

    expect($html)->toContain('<a href="mailto:test@example.com"');
});

test('converts GFM tables', function () {
    $markdown = "| Header 1 | Header 2 |\n|----------|----------|\n| Cell 1   | Cell 2   |";
    $html = Markdown::toHtml($markdown);

    expect($html)->toContain('<table>')
        ->and($html)->toContain('<thead>')
        ->and($html)->toContain('<th>Header 1</th>')
        ->and($html)->toContain('<th>Header 2</th>')
        ->and($html)->toContain('<tbody>')
        ->and($html)->toContain('<td>Cell 1</td>')
        ->and($html)->toContain('<td>Cell 2</td>');
});

test('converts GFM strikethrough', function () {
    $html = Markdown::toHtml('~~deleted text~~');

    expect($html)->toContain('<del>deleted text</del>');
});

test('converts GFM task lists', function () {
    $markdown = "- [x] Done task\n- [ ] Pending task";
    $html = Markdown::toHtml($markdown);

    expect($html)->toContain('<input')
        ->and($html)->toContain('checked')
        ->and($html)->toContain('Done task')
        ->and($html)->toContain('Pending task');
});

test('preprocesses bold-wrapped headings', function () {
    $html = Markdown::toHtml('**## Heading**');

    expect($html)->toContain('<h2>Heading</h2>')
        ->and($html)->not->toContain('<strong>');
});

test('preprocesses bold-wrapped headings at multiple levels', function () {
    $markdown = "**## Level 2**\n\n**### Level 3**";
    $html = Markdown::toHtml($markdown);

    expect($html)->toContain('<h2>Level 2</h2>')
        ->and($html)->toContain('<h3>Level 3</h3>')
        ->and($html)->not->toContain('<strong>');
});

test('does not alter legitimate bold text', function () {
    $html = Markdown::toHtml('**just bold**');

    expect($html)->toContain('<strong>just bold</strong>');
});

test('does not alter bold text that merely contains hash symbols', function () {
    $html = Markdown::toHtml('**#tag in bold**');

    expect($html)->toContain('<strong>#tag in bold</strong>');
});

test('preprocesses bold-wrapped horizontal rules', function () {
    expect(Markdown::toHtml('**---**'))->toContain('<hr')
        ->and(Markdown::toHtml('**___**'))->toContain('<hr')
        ->and(Markdown::toHtml('*******'))->toContain('<hr');
});

test('renders inline svg in markdown', function () {
    $markdown = 'Before <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><rect width="10" height="10" fill="#ccc"/></svg> after';
    $html = Markdown::toHtml($markdown);

    expect($html)->toContain('<svg')
        ->and($html)->toContain('<rect')
        ->and($html)->toContain('fill="#ccc"');
});

test('strips script tags from inline svg in markdown', function () {
    $markdown = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/><script>alert("xss")</script></svg>';
    $html = Markdown::toHtml($markdown);

    expect($html)->toContain('<svg')
        ->and($html)->toContain('<rect')
        ->and($html)->not->toContain('<script>')
        ->and($html)->not->toContain('alert');
});

test('strips event handlers from inline svg in markdown', function () {
    $markdown = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10" onload="alert(1)"/></svg>';
    $html = Markdown::toHtml($markdown);

    expect($html)->toContain('<svg')
        ->and($html)->not->toContain('onload')
        ->and($html)->not->toContain('alert');
});

test('still strips non-svg html tags', function () {
    $html = Markdown::toHtml('<div class="evil">content</div>');

    expect($html)->not->toContain('<div>')
        ->and($html)->not->toContain('class="evil"');
});
