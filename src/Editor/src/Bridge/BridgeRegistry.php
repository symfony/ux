<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge;

use Symfony\UX\Editor\Exception\UnknownBridgeException;

final class BridgeRegistry
{
    /**
     * @var array<string, BridgeInterface>
     */
    private array $bridges = [];

    /**
     * @param iterable<BridgeInterface> $bridges
     */
    public function __construct(iterable $bridges = [])
    {
        foreach ($bridges as $b) {
            if (isset($this->bridges[$b->getId()])) {
                throw new \LogicException(\sprintf('Duplicate bridge id "%s"', $b->getId()));
            }
            $this->bridges[$b->getId()] = $b;
        }
    }

    public function get(string $id): BridgeInterface
    {
        return $this->bridges[$id]
            ?? throw new UnknownBridgeException(\sprintf('Unknown bridge "%s". Registered: %s', $id, '' !== ($list = implode(', ', array_keys($this->bridges))) ? $list : '(none)'));
    }

    /**
     * @return array<string, BridgeInterface>
     */
    public function all(): array
    {
        return $this->bridges;
    }
}
