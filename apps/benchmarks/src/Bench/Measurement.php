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

final class Measurement
{
    public function __construct(
        public readonly float $requestsPerSecond,
        public readonly float $p50,
        public readonly float $p95,
        public readonly float $p99,
        public readonly int $errors,
    ) {
    }
}
