<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Cursor\CursorCodec;
use Symfony\UX\Pagination\Exception\RuntimeException;

#[CoversClass(CursorCodec::class)]
final class CursorCodecTest extends TestCase
{
    public function testConstructorMarksTheSecretAsSensitive(): void
    {
        $parameter = new \ReflectionMethod(CursorCodec::class, '__construct')->getParameters()[0];

        self::assertNotEmpty($parameter->getAttributes(\SensitiveParameter::class));
    }

    public function testEmptySecretIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('non-empty "ux_pagination.cursor.secret" or "kernel.secret"');
        new CursorCodec('');
    }

    public function testSignedVersionedRoundTrip(): void
    {
        $codec = new CursorCodec('application-secret');
        $token = $codec->encode([12, '2026-07-25'], true, 'order-a', 'products');

        self::assertSame([
            'values' => [12, '2026-07-25'],
            'forward' => true,
        ], $codec->decode($token, 'order-a', 'products'));
    }

    public function testTamperedTokenIsRejected(): void
    {
        $codec = new CursorCodec('application-secret');
        $token = $codec->encode([12], true, 'order-a', 'products');
        $token[-2] = 'A' === $token[-2] ? 'B' : 'A';

        $this->expectException(\InvalidArgumentException::class);
        $codec->decode($token, 'order-a', 'products');
    }

    public function testTokenCannotBeReusedForAnotherOrder(): void
    {
        $codec = new CursorCodec('application-secret');
        $token = $codec->encode([12], true, 'order-a', 'products');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not match this pagination order');
        $codec->decode($token, 'order-b', 'products');
    }

    public function testTokenCannotBeVerifiedWithAnotherSecret(): void
    {
        $token = new CursorCodec('first-secret')->encode([12], true, 'order-a', 'products');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('signature');
        new CursorCodec('second-secret')->decode($token, 'order-a', 'products');
    }

    public function testTokenCannotBeReusedForAnotherContext(): void
    {
        $codec = new CursorCodec('application-secret');
        $token = $codec->encode([12], true, 'order-a', 'products');

        $this->expectException(\InvalidArgumentException::class);
        $codec->decode($token, 'order-a', 'orders');
    }

    public function testUnsignedLegacyTokenIsRejected(): void
    {
        $token = base64_encode(json_encode(['v' => [12]], \JSON_THROW_ON_ERROR));

        $this->expectException(\InvalidArgumentException::class);
        new CursorCodec('application-secret')->decode($token, 'order-a', 'products');
    }

    public function testOversizedTokenIsRejectedBeforeDecoding(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CursorCodec('secret')->decode(str_repeat('a', 4097), 'order-a', 'products');
    }

    public function testTooManyOrderedValuesAreRejectedBeforeEncoding(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at most 16 ordered values');

        new CursorCodec('secret')->encode(range(1, 17), true, 'order-a', 'products');
    }

    public function testNonScalarOrderedValueIsRejectedBeforeEncoding(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cursor value');
        new CursorCodec('secret')->encode([new \stdClass()], true, 'order-a', 'products');
    }

    public function testNonFiniteFloatIsRejectedBeforeEncoding(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be finite');

        new CursorCodec('secret')->encode([\NAN], true, 'order-a', 'products');
    }

    public function testInvalidBase64IsRejectedBeforeDecoding(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cursor value');
        new CursorCodec('secret')->decode('*', 'order-a', 'products');
    }

    public function testOversizedPayloadIsRejectedBeforeEncoding(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('too large to encode safely');

        new CursorCodec('secret')->encode([str_repeat('a', 4000)], true, 'order-a', 'products');
    }
}
