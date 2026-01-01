<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark\Extension\Tabs\Renderer;

use App\Service\CommonMark\Extension\Tabs\Node\Tab;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class TabRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (!$node instanceof Tab) {
            throw new \InvalidArgumentException(\sprintf('Expected instance of "%s", got "%s"', Tab::class, $node::class));
        }

        return $childRenderer->renderNodes($node->children());
    }
}
