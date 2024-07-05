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
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\CompositeIdEntity;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<CompositeIdEntity>
 *
 * @method static CompositeIdEntity|Proxy          createOne(array $attributes = [])
 * @method static CompositeIdEntity[]|Proxy[]      createMany(int $number, array|callable $attributes = [])
 * @method static CompositeIdEntity|Proxy          find(object|array|mixed $criteria)
 * @method static CompositeIdEntity|Proxy          findOrCreate(array $attributes)
 * @method static CompositeIdEntity|Proxy          first(string $sortedField = 'id')
 * @method static CompositeIdEntity|Proxy          last(string $sortedField = 'id')
 * @method static CompositeIdEntity|Proxy          random(array $attributes = [])
 * @method static CompositeIdEntity|Proxy          randomOrCreate(array $attributes = []))
 * @method static CompositeIdEntity[]|Proxy[]      all()
 * @method static CompositeIdEntity[]|Proxy[]      findBy(array $attributes)
 * @method static CompositeIdEntity[]|Proxy[]      randomSet(int $number, array $attributes = []))
 * @method static CompositeIdEntity[]|Proxy[]      randomRange(int $min, int $max, array $attributes = []))
 * @method static EntityRepository|RepositoryProxy repository()
 * @method        CompositeIdEntity|Proxy          create(array|callable $attributes = [])
 */
class CompositeIdEntityFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return CompositeIdEntity::class;
    }

    protected function getDefaults(): array
    {
        return [
            'firstIdPart' => rand(1, \PHP_INT_MAX),
            'secondIdPart' => rand(1, \PHP_INT_MAX),
        ];
    }
}
