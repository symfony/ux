<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Tests\Unit\Context;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Disclose\Context\DoctrinePersistenceContextProvider;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class DoctrinePersistenceContextProviderTest extends TestCase
{
    public function testBuildsAContextOutOfAManagedSubject(): void
    {
        $subject = new \stdClass();

        $metadata = $this->createStub(ClassMetadata::class);
        $metadata->method('getIdentifierValues')->willReturn(['id' => 7]);

        $objectManager = $this->createStub(ObjectManager::class);
        $objectManager->method('getClassMetadata')->willReturn($metadata);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($objectManager);

        $provider = new DoctrinePersistenceContextProvider([$registry]);

        $context = $provider->create($subject, 'email', ['tenant' => 'acme']);

        self::assertSame(\stdClass::class, $context->class);
        self::assertSame(7, $context->id);
        self::assertSame('email', $context->field);
        self::assertSame(['tenant' => 'acme'], $context->getExtra());
    }

    public function testBuildsACompositeIdentifierContext(): void
    {
        $subject = new \stdClass();

        $metadata = $this->createStub(ClassMetadata::class);
        $metadata->method('getIdentifierValues')->willReturn(['client' => 3, 'number' => 'INV-42']);

        $objectManager = $this->createStub(ObjectManager::class);
        $objectManager->method('getClassMetadata')->willReturn($metadata);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($objectManager);

        $context = new DoctrinePersistenceContextProvider([$registry])->create($subject);

        self::assertSame(['client' => 3, 'number' => 'INV-42'], $context->id);
    }

    public function testReturnsNullForAnUnmanagedSubject(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn(null);

        $provider = new DoctrinePersistenceContextProvider([null, $registry]);

        self::assertNull($provider->create(new \stdClass()));
    }
}
