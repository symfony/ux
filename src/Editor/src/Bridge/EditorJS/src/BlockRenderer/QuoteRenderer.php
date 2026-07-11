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

final class QuoteRenderer implements BlockRendererInterface
{
    public function getBlockType(): string
    {
        return 'quote';
    }

    public function render(array $blockData, array $blockMeta = []): string
    {
        $text = htmlspecialchars((string) ($blockData['text'] ?? ''), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $cap = $blockData['caption'] ?? null;
        $body = \sprintf('<p>%s</p>', $text);
        if (\is_string($cap) && '' !== $cap) {
            $body .= \sprintf('<cite>%s</cite>', htmlspecialchars($cap, \ENT_QUOTES | \ENT_HTML5, 'UTF-8'));
        }

        return \sprintf('<blockquote>%s</blockquote>', $body);
    }
}
