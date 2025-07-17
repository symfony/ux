<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Fixtures\Factory;

use Symfony\Component\Uid\UuidV4;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Ingredient;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Ingredient>
 */
final class IngredientFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Ingredient::class;
    }

    protected function defaults(): array
    {
        return [
            'id' => new UuidV4(),
            'name' => self::faker()->text(),
        ];
    }
}
