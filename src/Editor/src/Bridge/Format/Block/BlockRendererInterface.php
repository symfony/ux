<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\Format\Block;

interface BlockRendererInterface
{
    public function getBlockType(): string;

    /**
     * @param array<string, mixed> $blockData
     * @param array<string, mixed> $blockMeta
     */
    public function render(array $blockData, array $blockMeta = []): string;
}
