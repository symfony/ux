<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
interface MapOptionsInterface
{
    /**
     * @internal
     */
    public static function fromArray(array $array): self;

    /**
     * @internal
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
