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

use Doctrine\ORM\EntityRepository;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\ProductTag;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends PersistentObjectFactory<ProductTag>
 */
final class ProductTagFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ProductTag::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->word(),
        ];
    }
}
