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

use Doctrine\ORM\EntityRepository;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\ForeignKeyIdEntity;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

use function Zenstruck\Foundry\lazy;

/**
 * @extends ModelFactory<ForeignKeyIdEntity>
 *
 * @method static ForeignKeyIdEntity|Proxy         createOne(array $attributes = [])
 * @method static ForeignKeyIdEntity[]|Proxy[]     createMany(int $number, array|callable $attributes = [])
 * @method static ForeignKeyIdEntity|Proxy         find(object|array|mixed $criteria)
 * @method static ForeignKeyIdEntity|Proxy         findOrCreate(array $attributes)
 * @method static ForeignKeyIdEntity|Proxy         first(string $sortedField = 'id')
 * @method static ForeignKeyIdEntity|Proxy         last(string $sortedField = 'id')
 * @method static ForeignKeyIdEntity|Proxy         random(array $attributes = [])
 * @method static ForeignKeyIdEntity|Proxy         randomOrCreate(array $attributes = []))
 * @method static ForeignKeyIdEntity[]|Proxy[]     all()
 * @method static ForeignKeyIdEntity[]|Proxy[]     findBy(array $attributes)
 * @method static ForeignKeyIdEntity[]|Proxy[]     randomSet(int $number, array $attributes = []))
 * @method static ForeignKeyIdEntity[]|Proxy[]     randomRange(int $min, int $max, array $attributes = []))
 * @method static EntityRepository|RepositoryProxy repository()
 * @method        ForeignKeyIdEntity|Proxy         create(array|callable $attributes = [])
 */
class ForeignKeyIdEntityFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return ForeignKeyIdEntity::class;
    }

    protected function getDefaults(): array
    {
        return ['id' => lazy(static fn () => new Entity1())];
    }
}
