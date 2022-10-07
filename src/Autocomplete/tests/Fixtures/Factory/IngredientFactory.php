<?php

namespace Symfony\UX\Autocomplete\Tests\Fixtures\Factory;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\UuidV4;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Ingredient;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Ingredient>
 *
 * @method static Ingredient|Proxy createOne(array $attributes = [])
 * @method static Ingredient[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Ingredient|Proxy find(object|array|mixed $criteria)
 * @method static Ingredient|Proxy findOrCreate(array $attributes)
 * @method static Ingredient|Proxy first(string $sortedField = 'id')
 * @method static Ingredient|Proxy last(string $sortedField = 'id')
 * @method static Ingredient|Proxy random(array $attributes = [])
 * @method static Ingredient|Proxy randomOrCreate(array $attributes = []))
 * @method static Ingredient[]|Proxy[] all()
 * @method static Ingredient[]|Proxy[] findBy(array $attributes)
 * @method static Ingredient[]|Proxy[] randomSet(int $number, array $attributes = []))
 * @method static Ingredient[]|Proxy[] randomRange(int $min, int $max, array $attributes = []))
 * @method static EntityRepository|RepositoryProxy repository()
 * @method Ingredient|Proxy create(array|callable $attributes = [])
 */
final class IngredientFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'id' => new UuidV4(),
            'name' => self::faker()->text(),
        ];
    }

    protected function initialize(): self
    {
        return $this;
    }

    protected static function getClass(): string
    {
        return Ingredient::class;
    }
}
