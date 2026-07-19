<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\Popover\Renderer;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Symfony\UX\Toolkit\Markdown\Extension\Popover\Node\Popover;

final class PopoverRenderer implements NodeRendererInterface
{
    private int $counter = 0;
    private ?CommonMarkConverter $descriptionConverter = null;

    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (!$node instanceof Popover) {
            throw new \InvalidArgumentException(\sprintf('Expected instance of "%s", got "%s"', Popover::class, $node::class));
        }

        // Popover is inline: trim the template's trailing newline so it does not leak whitespace into the paragraph.
        return trim($this->twig->render('@UXToolkit/markdown/popover.html.twig', [
            'id' => 'prop-description-'.(++$this->counter),
            'description' => $this->renderDescription($node->getDescription()),
        ]));
    }

    /**
     * Renders the prop description (Markdown) as inline HTML so `code`, **bold**, links…
     * show up formatted inside the popover. Raw HTML in descriptions is escaped for safety.
     */
    private function renderDescription(string $markdown): string
    {
        $this->descriptionConverter ??= new CommonMarkConverter(['html_input' => 'escape']);

        $html = trim($this->descriptionConverter->convert($markdown)->getContent());

        // Descriptions are single-line: unwrap the enclosing paragraph so the popover
        // renders inline content without the paragraph's default block margins.
        return preg_replace('/^<p>(.*)<\/p>$/s', '$1', $html) ?? $html;
    }
}
