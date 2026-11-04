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

use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\UuidIdEntity;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<UuidIdEntity>
 */
class UuidIdEntityFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return UuidIdEntity::class;
    }

    protected function defaults(): array
    {
        return [];
    }
}
