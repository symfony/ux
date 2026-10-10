<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Fixtures;

/**
 * Stands in for the Doctrine entity a controller receives: crumb expressions
 * dereference it, which is exactly what must not happen before render time.
 */
final class Product
{
    public function __construct(
        public readonly string $slug = 'blue-sneakers',
        public readonly string $name = 'Blue sneakers',
        public readonly string $state = 'published',
        public readonly \DateTimeImmutable $releasedAt = new \DateTimeImmutable('2024-01-01 00:00:00'),
    ) {
    }
}
