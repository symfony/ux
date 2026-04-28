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

final class BlockRendererRegistry
{
    /**
     * @var array<string, BlockRendererInterface>
     */
    private array $byType = [];

    /**
     * @param iterable<BlockRendererInterface> $renderers
     */
    public function __construct(iterable $renderers = [])
    {
        foreach ($renderers as $r) {
            $this->byType[$r->getBlockType()] = $r;
        }
    }

    public function get(string $type): ?BlockRendererInterface
    {
        return $this->byType[$type] ?? null;
    }
}
