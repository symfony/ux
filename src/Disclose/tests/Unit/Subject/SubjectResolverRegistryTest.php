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

use PHPUnit\Framework\TestCase;
use Symfony\UX\Disclose\Context\DiscloseContext;
use Symfony\UX\Disclose\Subject\Exception\UnmanagedSubjectClassException;
use Symfony\UX\Disclose\Subject\SubjectResolverInterface;
use Symfony\UX\Disclose\Subject\SubjectResolverRegistry;
use Symfony\UX\Disclose\Tests\Fixtures\Subject\FixtureData;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class SubjectResolverRegistryTest extends TestCase
{
    public function testUsesTheFirstResolverSupportingTheContext(): void
    {
        $unsupported = $this->createStub(SubjectResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $supported = $this->createMock(SubjectResolverInterface::class);
        $supported->method('supports')->willReturn(true);
        $supported->expects(self::once())->method('resolve')->willReturn(new FixtureData());

        $registry = new SubjectResolverRegistry([$unsupported, $supported]);
        $context = DiscloseContext::create(FixtureData::class, 42);

        self::assertInstanceOf(FixtureData::class, $registry->resolve($context));
    }

    public function testThrowsWhenNoResolverSupportsTheContext(): void
    {
        $unsupported = $this->createStub(SubjectResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $registry = new SubjectResolverRegistry([$unsupported]);
        $context = DiscloseContext::create('App\DoesNotExist', 42);

        $this->expectException(UnmanagedSubjectClassException::class);

        $registry->resolve($context);
    }
}
