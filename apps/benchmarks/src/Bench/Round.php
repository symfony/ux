<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Bench;

final class Round
{
    public function __construct(
        public readonly string $mode,
        public readonly string $target,
        public readonly int $round,
        public readonly Measurement $measurement,
    ) {
    }
}
