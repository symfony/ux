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

final class ParagraphRenderer implements BlockRendererInterface
{
    public function getBlockType(): string
    {
        return 'paragraph';
    }

    public function render(array $blockData, array $blockMeta = []): string
    {
        return '<p>'.htmlspecialchars((string) ($blockData['text'] ?? ''), \ENT_QUOTES | \ENT_HTML5, 'UTF-8').'</p>';
    }
}
