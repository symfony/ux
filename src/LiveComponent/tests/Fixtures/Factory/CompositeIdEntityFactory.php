<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Factory;

use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\CompositeIdEntity;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<CompositeIdEntity>
 */
class CompositeIdEntityFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return CompositeIdEntity::class;
    }

    protected function defaults(): array
    {
        return [
            'firstIdPart' => rand(1, \PHP_INT_MAX),
            'secondIdPart' => rand(1, \PHP_INT_MAX),
        ];
    }
}
