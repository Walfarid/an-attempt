<?php

// Test file lives at tests/Unit/Support/RulesIndexTest.php — 3 levels below the repo root.
const REPO_ROOT = __DIR__.'/../../..';
const RULES_ROOT = REPO_ROOT.'/.ai/rules/';

function rulesIndexHasRealFile(string $glob): bool
{
    $normalized = str_replace(['{', '}'], ['[', ']'], $glob);
    $normalized = str_replace(',', '|', $normalized);

    // Directory/prefix globs match trivially; keep only those with concrete patterns.
    if (in_array($normalized, ['.ai/rules/**', 'app/**', '**'], true)) {
        return true;
    }

    // Directory/prefix globs: match when their base directory exists (or has matching files).
    if (str_contains($normalized, '**')) {
        $doubleStarPos = strpos($normalized, '**');

        if ($doubleStarPos === false) {
            return false;
        }

        // base = path before the first '**'; anything after matches against filenames recursively.
        $base = rtrim(substr($normalized, 0, $doubleStarPos), '/');
        $tail = substr($normalized, $doubleStarPos + 2);
        $tail = ltrim($tail, '/');

        if ($base === '') {
            return true;
        }

        if (! is_dir(REPO_ROOT.'/'.$base)) {
            return false;
        }

        // Convert simple glob tail (e.g. *.vue) to regex.
        $tailRegex = preg_quote($tail, '~');
        $tailRegex = str_replace(['\*', '\?'], ['.*', '.'], $tailRegex);
        $pattern = '~^'.$tailRegex.'$~';

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(REPO_ROOT.'/'.$base, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($tail === '' || preg_match($pattern, $file->getFilename())) {
                return true;
            }
        }

        return false;
    }

    $matches = glob(REPO_ROOT.'/'.$normalized, GLOB_BRACE);

    return $matches !== false && count($matches) > 0;
}

test('every rule file is listed in the rules index', function () {
    $index = file_get_contents(RULES_ROOT.'index.md');
    $ruleFiles = glob(RULES_ROOT.'*.md') ?: [];
    $ruleFiles = array_values(array_filter($ruleFiles, fn (string $path) => basename($path) !== 'index.md'));

    expect($ruleFiles)->not->toBeEmpty();

    foreach ($ruleFiles as $path) {
        $name = basename($path);

        $present = is_string($index) && str_contains($index, ".ai/rules/{$name}");

        expect($present)->toBeTrue();
    }
});

test('every frontmatter paths entry resolves to an existing file', function () {
    $ruleFiles = glob(RULES_ROOT.'*.md') ?: [];

    foreach ($ruleFiles as $path) {
        if (basename($path) === 'index.md') {
            continue;
        }

        $name = basename($path);
        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            continue;
        }

        if (! preg_match('/^\s*---\s*\n(.*?)\n---\s*\n?/s', $contents, $block)) {
            continue; // no YAML frontmatter — free-text 'Applies to:' files are covered by the 'Applies to' test
        }

        preg_match_all('/^\s*-\s+(.+?)\s*$/m', $block[1], $matches);
        $paths = array_map(fn (string $p) => trim($p, " \t\n\r\0\x0B'\""), $matches[1]);

        expect($paths)->not->toBeEmpty();

        foreach ($paths as $glob) {
            $resolvable = rulesIndexHasRealFile($glob);

            expect($resolvable)->toBeTrue();
        }
    }
});

test('every Applies to column in the index resolves to at least one file', function () {
    $indexLines = file(RULES_ROOT.'index.md', FILE_IGNORE_NEW_LINES);

    if ($indexLines === false) {
        $indexLines = [];
    }

    $rows = collect($indexLines)->filter(fn (string $line) => str_starts_with($line, '| '))->map(function (string $line) {
        $cells = array_map('trim', explode('|', $line));

        return ['globs' => $cells[1] ?? '', 'rule' => $cells[2] ?? ''];
    })->filter(fn (array $row) => $row['rule'] !== '' && str_contains($row['rule'], '.ai/rules/'));
    // extract all backtick-enclosed globs from the cell; comma-splitting would break multi-glob cells
    $globPattern = '/`([^`]+)`/';

    foreach ($rows as $row) {
        preg_match_all($globPattern, $row['globs'], $matches);
        $globs = array_map('trim', $matches[1]);

        $nonEmpty = $globs !== [];

        expect($nonEmpty)->toBeTrue();

        foreach ($globs as $glob) {
            $resolvable = rulesIndexHasRealFile($glob);

            expect($resolvable)->toBeTrue();
        }
    }
});

test('the rules index has no duplicate rule file entries', function () {
    $index = file_get_contents(RULES_ROOT.'index.md');

    if (! is_string($index)) {
        return;
    }

    preg_match_all('/\.ai\/rules\/([\w.-]+\.md)/', $index, $matches);

    expect(collect($matches[1])->duplicates()->values()->all())->toBe([]);
});

test('the index maps every rule file through .ai/rules/*.md consistently', function () {
    $index = file_get_contents(RULES_ROOT.'index.md');

    if (! is_string($index)) {
        return;
    }

    preg_match_all('/\.ai\/rules\/([\w.-]+\.md)/', $index, $matches);
    $indexed = collect($matches[1])->unique();

    foreach ($indexed as $name) {
        $exists = file_exists(RULES_ROOT."{$name}");

        expect($exists)->toBeTrue();
    }
});
