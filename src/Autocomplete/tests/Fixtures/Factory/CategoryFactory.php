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

use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Category>
 */
final class CategoryFactory extends PersistentObjectFactory
{
    protected function defaults(): array
    {
        $name = self::faker()->name();
        return [
            'name' => $name,
            'code' => strtolower(str_replace(' ', '_', $name)),
        ];
    }

    public static function class(): string
    {
        return Category::class;
    }
}
