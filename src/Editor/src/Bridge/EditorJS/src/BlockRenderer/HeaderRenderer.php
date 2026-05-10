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

final class HeaderRenderer implements BlockRendererInterface
{
    public function getBlockType(): string
    {
        return 'header';
    }

    public function render(array $blockData, array $blockMeta = []): string
    {
        $level = max(2, min(6, (int) ($blockData['level'] ?? 2)));
        $text = htmlspecialchars((string) ($blockData['text'] ?? ''), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');

        return sprintf('<h%1$d>%2$s</h%1$d>', $level, $text);
    }
}
