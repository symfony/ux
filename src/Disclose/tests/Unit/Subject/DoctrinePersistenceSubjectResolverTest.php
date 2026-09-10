<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Tests\Unit\Subject;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Disclose\Context\DiscloseContext;
use Symfony\UX\Disclose\Subject\DoctrinePersistenceSubjectResolver;
use Symfony\UX\Disclose\Subject\Exception\SubjectNotFoundException;
use Symfony\UX\Disclose\Subject\Exception\UnmanagedSubjectClassException;
use Symfony\UX\Disclose\Tests\Fixtures\Subject\FixtureData;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class DoctrinePersistenceSubjectResolverTest extends TestCase
{
    public function testDoesNotSupportAClassOutsideTheRegistry(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn(null);

        $resolver = new DoctrinePersistenceSubjectResolver($registry);

        self::assertFalse($resolver->supports(DiscloseContext::create(FixtureData::class, 42)));
    }

    public function testResolvesAManagedSubject(): void
    {
        $subject = new FixtureData('the-secret');

        $objectManager = $this->createStub(ObjectManager::class);
        $objectManager->method('find')->willReturn($subject);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($objectManager);

        $resolver = new DoctrinePersistenceSubjectResolver($registry);

        self::assertTrue($resolver->supports(DiscloseContext::create(FixtureData::class, 42)));
        self::assertSame($subject, $resolver->resolve(DiscloseContext::create(FixtureData::class, 42)));
    }

    public function testThrowsWhenTheSubjectCannotBeFound(): void
    {
        $objectManager = $this->createStub(ObjectManager::class);
        $objectManager->method('find')->willReturn(null);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($objectManager);

        $resolver = new DoctrinePersistenceSubjectResolver($registry);

        $this->expectException(SubjectNotFoundException::class);

        $resolver->resolve(DiscloseContext::create(FixtureData::class, 999));
    }

    public function testRejectsAClassOutsideAnyObjectManager(): void
    {
        $resolver = new DoctrinePersistenceSubjectResolver(null);

        self::assertFalse($resolver->supports(DiscloseContext::create(FixtureData::class, 42)));
        $this->expectException(UnmanagedSubjectClassException::class);

        $resolver->resolve(DiscloseContext::create(FixtureData::class, 42));
    }
}
