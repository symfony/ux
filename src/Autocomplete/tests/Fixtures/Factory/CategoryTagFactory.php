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

use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\CategoryTag;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CategoryTag>
 */
final class CategoryTagFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return CategoryTag::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->word(),
        ];
    }
}
