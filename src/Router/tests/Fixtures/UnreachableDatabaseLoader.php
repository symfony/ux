<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests\Fixtures;

use Symfony\Component\Config\Loader\Loader;

final class UnreachableDatabaseLoader extends Loader
{
    public function load(mixed $resource, ?string $type = null): mixed
    {
        throw new \RuntimeException('The database is not reachable.');
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'database' === $type;
    }
}
