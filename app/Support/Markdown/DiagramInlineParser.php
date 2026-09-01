<?php

namespace App\Support\Markdown;

use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

/**
 * Parses the `@@diagram <name>@@` inline token into a DiagramNode.
 *
 * The name is restricted to lowercase letters, digits and dashes so the
 * rendered iframe can only target same-origin files under /diagrams.
 */
final class DiagramInlineParser implements InlineParserInterface
{
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('@@diagram\s+([a-z0-9-]+)@@');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $name = $inlineContext->getMatches()[1];

        $inlineContext->getCursor()->advanceBy($inlineContext->getFullMatchLength());

        $inlineContext->getContainer()->appendChild(new DiagramNode($name));

        return true;
    }
}
