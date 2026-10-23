<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Integration\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Hydration\DoctrineEntityHydrationExtension;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\AliasedEntity;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\AliasedEntityInterface;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\CompositeIdEntity;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\ForeignKeyIdEntity;
use Symfony\UX\LiveComponent\Tests\Fixtures\Factory\CompositeIdEntityFactory;
use Symfony\UX\LiveComponent\Tests\Fixtures\Factory\ForeignKeyIdEntityFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DoctrineEntityHydrationExtensionTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testCompositeId(): void
    {
        $compositeIdEntity = CompositeIdEntityFactory::createOne();

        /** @var DoctrineEntityHydrationExtension $extension */
        $extension = self::getContainer()->get('ux.live_component.doctrine_entity_hydration_extension');

        self::assertSame(
            $compositeIdEntity,
            $extension->hydrate($extension->dehydrate($compositeIdEntity), CompositeIdEntity::class)
        );
    }

    public function testForeignKeyId(): void
    {
        $foreignKeyIdEntity = ForeignKeyIdEntityFactory::createOne();

        /** @var DoctrineEntityHydrationExtension $extension */
        $extension = self::getContainer()->get('ux.live_component.doctrine_entity_hydration_extension');

        $dehydrated = $extension->dehydrate($foreignKeyIdEntity);

        self::assertSame($foreignKeyIdEntity->id->id, $dehydrated);
        self::assertSame($foreignKeyIdEntity, $extension->hydrate($dehydrated, ForeignKeyIdEntity::class));
    }

    public function testSupportsInterfaceResolvedToEntity(): void
    {
        /** @var DoctrineEntityHydrationExtension $extension */
        $extension = self::getContainer()->get('ux.live_component.doctrine_entity_hydration_extension');

        self::assertTrue($extension->supports(AliasedEntityInterface::class));
        self::assertTrue($extension->supports(AliasedEntity::class));
        self::assertFalse($extension->supports(\Countable::class));
    }

    public function testHydrateInterfaceResolvedToEntity(): void
    {
        /** @var DoctrineEntityHydrationExtension $extension */
        $extension = self::getContainer()->get('ux.live_component.doctrine_entity_hydration_extension');
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $entity = new AliasedEntity();
        $entity->name = 'foo';
        $em->persist($entity);
        $em->flush();

        $dehydrated = $extension->dehydrate($entity);

        self::assertSame($entity->id, $dehydrated);
        self::assertSame($entity, $extension->hydrate($dehydrated, AliasedEntityInterface::class));
        self::assertNull($extension->hydrate(null, AliasedEntityInterface::class));
    }
}
