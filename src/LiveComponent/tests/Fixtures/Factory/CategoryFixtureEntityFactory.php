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

use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\CategoryFixtureEntity;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class CategoryFixtureEntityFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->name(),
        ];
    }

    public static function class(): string
    {
        return CategoryFixtureEntity::class;
    }
}
