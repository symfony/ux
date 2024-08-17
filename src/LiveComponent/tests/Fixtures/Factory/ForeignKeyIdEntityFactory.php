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

use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\ForeignKeyIdEntity;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

use function Zenstruck\Foundry\lazy;

/**
 * @extends PersistentProxyObjectFactory<ForeignKeyIdEntity>
 */
class ForeignKeyIdEntityFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return ForeignKeyIdEntity::class;
    }

    protected function defaults(): array
    {
        return ['id' => lazy(static fn () => new Entity1())];
    }
}
