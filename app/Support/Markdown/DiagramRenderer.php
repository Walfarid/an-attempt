<?php

namespace App\Support\Markdown;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * Renders a DiagramNode as a same-origin iframe pointing at
 * /diagrams/<name>.html. The name is validated by the inline parser's
 * restricted regex, so the src can never escape the diagrams directory.
 */
final class DiagramRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (! $node instanceof DiagramNode) {
            throw new \InvalidArgumentException('Incompatible node type '.get_class($node));
        }

        return (string) new HtmlElement('iframe', [
            'src' => '/diagrams/'.$node->name,
            'class' => 'diagram-embed',
            'title' => 'Interactive diagram: '.$node->name,
            'loading' => 'lazy',
            'allowfullscreen' => '',
        ], '');
    }
}
