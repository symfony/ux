<?php

namespace Symfony\UX\Autocomplete\Tests\Fixtures\Factory;

use Doctrine\ORM\EntityRepository;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Product;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Product>
 *
 * @method static Product|Proxy createOne(array $attributes = [])
 * @method static Product[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Product|Proxy find(object|array|mixed $criteria)
 * @method static Product|Proxy findOrCreate(array $attributes)
 * @method static Product|Proxy first(string $sortedField = 'id')
 * @method static Product|Proxy last(string $sortedField = 'id')
 * @method static Product|Proxy random(array $attributes = [])
 * @method static Product|Proxy randomOrCreate(array $attributes = []))
 * @method static Product[]|Proxy[] all()
 * @method static Product[]|Proxy[] findBy(array $attributes)
 * @method static Product[]|Proxy[] randomSet(int $number, array $attributes = []))
 * @method static Product[]|Proxy[] randomRange(int $min, int $max, array $attributes = []))
 * @method static EntityRepository|RepositoryProxy repository()
 * @method Product|Proxy create(array|callable $attributes = [])
 */
final class ProductFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->text(),
            'category' => CategoryFactory::new(),
            'price' => self::faker()->numberBetween(1000, 9999),
            'description' => self::faker()->paragraph(),
        ];
    }

    protected function initialize(): self
    {
        return $this;
    }

    public function disable(): self
    {
        return $this->addState(['isEnabled' => false]);
    }

    protected static function getClass(): string
    {
        return Product::class;
    }
}
