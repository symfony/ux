<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\Alert\Renderer;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Symfony\UX\Toolkit\Markdown\Extension\Alert\Node\Alert;

final class AlertRenderer implements NodeRendererInterface
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (!$node instanceof Alert) {
            throw new \InvalidArgumentException(\sprintf('Expected instance of "%s", got "%s"', Alert::class, $node::class));
        }

        return $this->twig->render('@UXToolkit/markdown/alert.html.twig', [
            'variant' => $node->getType(),
            'content' => $childRenderer->renderNodes($node->children()),
        ]);
    }
}
