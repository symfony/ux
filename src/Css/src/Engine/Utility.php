<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Engine;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Utility
{
    /**
     * @param list<string> $shorthands
     * @param mixed        $values     a token category, a list, a map of aliases, a type or a probed function
     */
    public function __construct(
        public readonly string $property,
        public readonly ?string $className,
        public readonly array $shorthands,
        public readonly mixed $values = null,
        public readonly ?string $layer = null,
    ) {
    }
}
