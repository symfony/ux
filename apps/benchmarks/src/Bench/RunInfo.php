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

final class RunInfo
{
    public function __construct(
        public readonly string $command,
        public readonly string $date,
        public readonly string $frankenPhpVersion,
        public readonly string $cpu,
        public readonly int $cores,
        public readonly string $os,
        public readonly int $concurrency,
        public readonly int $warmup,
        public readonly int $rounds,
        public readonly int $seconds,
        public readonly float $loadBefore,
        public readonly float $loadAfter,
    ) {
    }
}
