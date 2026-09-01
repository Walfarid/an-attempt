<?php

namespace App\Support\Markdown;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * An interactive diagram embedded in a post body via the
 * `@@diagram <name>@@` token. The rendered iframe only points at
 * same-origin files under /diagrams.
 */
final class DiagramNode extends AbstractInline
{
    public function __construct(public readonly string $name) {}
}
