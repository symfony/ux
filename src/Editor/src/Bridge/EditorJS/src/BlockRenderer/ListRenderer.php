<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer;

use Symfony\UX\Editor\Bridge\Format\Block\BlockRendererInterface;

final class ListRenderer implements BlockRendererInterface
{
    public function getBlockType(): string
    {
        return 'list';
    }

    public function render(array $blockData, array $blockMeta = []): string
    {
        $tag = (($blockData['style'] ?? 'unordered') === 'ordered') ? 'ol' : 'ul';
        $items = '';
        foreach ((array) ($blockData['items'] ?? []) as $i) {
            $items .= '<li>'.htmlspecialchars((string) $i, \ENT_QUOTES | \ENT_HTML5, 'UTF-8').'</li>';
        }

        return \sprintf('<%1$s>%2$s</%1$s>', $tag, $items);
    }
}
