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
        $compositeIdEntity = CompositeIdEntityFactory::createOne()->_real();

        /** @var DoctrineEntityHydrationExtension $extension */
        $extension = self::getContainer()->get('ux.live_component.doctrine_entity_hydration_extension');

        self::assertSame(
            $compositeIdEntity,
            $extension->hydrate($extension->dehydrate($compositeIdEntity), CompositeIdEntity::class)
        );
    }

    public function testForeignKeyId(): void
    {
        $foreignKeyIdEntity = ForeignKeyIdEntityFactory::createOne()->_real();

        /** @var DoctrineEntityHydrationExtension $extension */
        $extension = self::getContainer()->get('ux.live_component.doctrine_entity_hydration_extension');

        $dehydrated = $extension->dehydrate($foreignKeyIdEntity);

        self::assertSame($foreignKeyIdEntity->id->id, $dehydrated);
        self::assertSame($foreignKeyIdEntity, $extension->hydrate($dehydrated, ForeignKeyIdEntity::class));
    }


    public function testSupportInterface(): void
    {
        /** @var DoctrineEntityHydrationExtension $extension */
        $extension = self::getContainer()->get('ux.live_component.doctrine_entity_hydration_extension');

        self::assertTrue($extension->supports(AliasedEntityInterface::class),"AliasedEntityInterface should be supported");
        self::assertTrue($extension->supports(AliasedEntity::class),"AliasedEntity should be supported");
        self::assertFalse($extension->supports('UnknownClass'),"UnknownClass should not be supported");
    }

    public function testHydrationFromInterface(): void
    {
        /** @var DoctrineEntityHydrationExtension $extension */
        $extension = self::getContainer()->get('ux.live_component.doctrine_entity_hydration_extension');
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $existingEntity = new AliasedEntity();
        $existingEntity->name = 'foo';

        $em->persist($existingEntity);
        $em->flush();

        $dehydratedData = $extension->dehydrate($existingEntity);

        $entityFromDehydratation = $extension->hydrate($dehydratedData, AliasedEntityInterface::class);

        self::assertSame($existingEntity, $entityFromDehydratation,'instance should be the same');
        self::assertNull($extension->hydrate(null, AliasedEntityInterface::class),'should return null if null is passed');
    }
}
