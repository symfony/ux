<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\Tabs\Renderer;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\Node\Tab;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\Node\Tabs;

final class TabsRenderer implements NodeRendererInterface
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (!$node instanceof Tabs) {
            throw new \InvalidArgumentException(\sprintf('Expected instance of "%s", got "%s"', Tabs::class, $node::class));
        }

        $tabs = [];
        foreach ($node->children() as $child) {
            if ($child instanceof Tab) {
                $title = $child->getTitle();
                $tabs[] = [
                    'id' => hash('xxh3', $title),
                    'title' => $title,
                    'content' => $childRenderer->renderNodes($child->children()),
                ];
            }
        }

        if ([] === $tabs) {
            throw new \RuntimeException(\sprintf('The "%s" block must contain at least one "%s" block.', Tabs::class, Tab::class));
        }

        return $this->twig->render('@UXToolkit/markdown/tabs.html.twig', [
            'tabs' => $tabs,
            'active_tab_id' => $tabs[0]['id'],
        ]);
    }
}
