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
use Symfony\UX\Disclose\Context\DiscloseContext;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class DiscloseContextTest extends TestCase
{
    public function testSerializesBackAndForth(): void
    {
        $context = DiscloseContext::create('App\Entity\User', 42, 'email', ['tenant' => 'acme']);

        $restored = DiscloseContext::fromArray($context->toArray());

        self::assertSame('App\Entity\User', $restored->class);
        self::assertSame(42, $restored->id);
        self::assertSame('email', $restored->field);
        self::assertSame(['tenant' => 'acme'], $restored->getExtra());
        self::assertTrue($restored->hasField());
    }

    public function testSupportsCompositeIdentifiers(): void
    {
        $context = DiscloseContext::create('App\Entity\Invoice', ['client' => 7, 'number' => 'INV-42']);

        self::assertSame(['client' => 7, 'number' => 'INV-42'], $context->id);
    }

    public function testFieldCanBeEmpty(): void
    {
        $context = DiscloseContext::create('App\Entity\User', 42);

        self::assertFalse($context->hasField());
        self::assertNull(DiscloseContext::fromArray($context->toArray())->field);
    }

    public function testWithFieldAndWithExtraAreImmutable(): void
    {
        $context = DiscloseContext::create('App\Entity\User', 42);

        $withField = $context->withField('email');
        $withExtra = $withField->withExtra(['format' => 'masked']);

        self::assertNull($context->field);
        self::assertSame('email', $withField->field);
        self::assertSame([], $withField->getExtra());
        self::assertSame('masked', $withExtra->get('format'));
        self::assertNull($withExtra->get('missing'));
    }

    public function testRejectsUnknownPayloadKeys(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DiscloseContext::fromArray(['class' => 'App\Entity\User', 'id' => 42, 'totally_unexpected' => true]);
    }

    public function testRejectsAMissingClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DiscloseContext::fromArray(['id' => 42]);
    }

    public function testRejectsAnInvalidId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DiscloseContext::fromArray(['class' => 'App\Entity\User', 'id' => ['nested' => ['array']]]);
    }
}
