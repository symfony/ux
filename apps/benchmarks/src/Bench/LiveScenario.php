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

final class LiveScenario
{
    /**
     * @param array<string, mixed> $updated
     * @param array<string, mixed> $args
     */
    public function __construct(
        public readonly string $page,
        public readonly string $component,
        public readonly array $updated = [],
        public readonly ?string $action = null,
        public readonly array $args = [],
        public readonly ?int $selectRows = null,
    ) {
    }
}
