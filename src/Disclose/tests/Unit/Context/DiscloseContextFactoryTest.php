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

use PHPUnit\Framework\TestCase;
use Symfony\UX\Disclose\Context\ContextProviderInterface;
use Symfony\UX\Disclose\Context\DiscloseContext;
use Symfony\UX\Disclose\Context\DiscloseContextFactory;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class DiscloseContextFactoryTest extends TestCase
{
    public function testCreatesAContextFromScratch(): void
    {
        $factory = new DiscloseContextFactory();

        $context = $factory->create('App\Entity\User', 42, 'email', ['tenant' => 'acme']);

        self::assertSame('App\Entity\User', $context->class);
        self::assertSame(42, $context->id);
        self::assertSame('email', $context->field);
        self::assertSame(['tenant' => 'acme'], $context->getExtra());
    }

    public function testBuildsAContextFromAnObjectThroughTheFirstSupportingProvider(): void
    {
        $expected = DiscloseContext::create(\stdClass::class, '99', 'label', ['x' => 1]);

        $unsupported = $this->createStub(ContextProviderInterface::class);
        $unsupported->method('create')->willReturn(null);

        $supported = $this->createStub(ContextProviderInterface::class);
        $supported->method('create')->willReturn($expected);

        $factory = new DiscloseContextFactory([$unsupported, $supported]);
        $subject = new \stdClass();

        self::assertSame($expected, $factory->createFromObject($subject, 'label', ['x' => 1]));
    }

    public function testFailsWhenNoProviderHandlesTheObject(): void
    {
        $factory = new DiscloseContextFactory();

        $this->expectException(\LogicException::class);

        $factory->createFromObject(new \stdClass());
    }
}
