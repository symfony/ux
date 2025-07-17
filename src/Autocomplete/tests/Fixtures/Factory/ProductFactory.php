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
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Product;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Product>
 */
final class ProductFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Product::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->text(),
            'category' => CategoryFactory::new(),
            'price' => self::faker()->numberBetween(1000, 9999),
            'description' => self::faker()->paragraph(),
        ];
    }

    public function disable(): self
    {
        return $this->with(['isEnabled' => false]);
    }
}
