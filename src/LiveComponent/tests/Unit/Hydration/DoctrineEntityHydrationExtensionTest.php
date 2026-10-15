<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\Hydration\DoctrineEntityHydrationExtension;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;

final class DoctrineEntityHydrationExtensionTest extends TestCase
{
    public function testItLooksUpTheManagerOfAClassOnce(): void
    {
        $entity = new Entity1();
        $entity->id = 42;

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getIdentifierValues')->with($entity)->willReturn(['id' => 42]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->with(Entity1::class)->willReturn($metadata);
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->once())->method('getManagerForClass')->with(Entity1::class)->willReturn($entityManager);

        $extension = new DoctrineEntityHydrationExtension([$registry]);

        $this->assertTrue($extension->supports(Entity1::class));
        $this->assertSame(42, $extension->dehydrate($entity));
        $this->assertSame(42, $extension->dehydrate($entity));
    }

    public function testItLooksUpAClassWithoutManagerOnce(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->once())->method('getManagerForClass')->with(\stdClass::class)->willReturn(null);

        $extension = new DoctrineEntityHydrationExtension([$registry]);

        $this->assertFalse($extension->supports(\stdClass::class));
        $this->assertFalse($extension->supports(\stdClass::class));
    }
}
