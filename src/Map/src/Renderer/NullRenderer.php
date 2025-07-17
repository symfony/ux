<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Renderer;

use Symfony\UX\Map\Exception\LogicException;
use Symfony\UX\Map\Map;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class NullRenderer implements RendererInterface
{
    public function __construct(
        private readonly array $availableBridges = [],
    ) {
    }

    public function renderMap(Map $map, array $attributes = []): string
    {
        $message = 'You must install at least one bridge package to use the Symfony UX Map component.';
        if ($this->availableBridges) {
            $message .= \PHP_EOL.'Try running '.implode(' or ', array_map(fn ($name) => \sprintf('"composer require %s"', $name), $this->availableBridges)).'.';
        }

        throw new LogicException($message);
    }

    public function __toString(): string
    {
        return 'null://null';
    }
}
