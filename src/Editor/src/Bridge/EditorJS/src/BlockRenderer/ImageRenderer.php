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

final class ImageRenderer implements BlockRendererInterface
{
    public function getBlockType(): string
    {
        return 'image';
    }

    public function render(array $blockData, array $blockMeta = []): string
    {
        $url = (string) ($blockData['file']['url'] ?? '');
        if ('' === $url) {
            return '';
        }
        $alt = htmlspecialchars((string) ($blockData['alt'] ?? ''), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $caption = $blockData['caption'] ?? null;
        $img = \sprintf('<img src="%s" alt="%s">', htmlspecialchars($url, \ENT_QUOTES | \ENT_HTML5, 'UTF-8'), $alt);
        if (\is_string($caption) && '' !== $caption) {
            return \sprintf('<figure>%s<figcaption>%s</figcaption></figure>', $img, htmlspecialchars($caption, \ENT_QUOTES | \ENT_HTML5, 'UTF-8'));
        }

        return \sprintf('<figure>%s</figure>', $img);
    }
}
