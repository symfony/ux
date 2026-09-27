<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Bridge\Glide\FormatNegotiator;
use Symfony\UX\Image\Bridge\Glide\GlideProvider;

final class FormatNegotiatorTest extends TestCase
{
    #[DataProvider('provideAcceptHeaders')]
    public function testFormatNegotiation(?string $accept, string $expected): void
    {
        self::assertSame($expected, new FormatNegotiator()->negotiate($accept, ['avif', 'webp', 'jpeg'], 'jpeg'));
    }

    public static function provideAcceptHeaders(): iterable
    {
        yield 'avif preferred' => ['image/avif,image/webp,*/*', 'avif'];
        yield 'webp only' => ['image/webp,*/*', 'webp'];
        yield 'neither' => ['text/html,*/*', 'jpeg'];
        yield 'no header' => [null, 'jpeg'];
        yield 'explicitly refused avif' => ['image/avif;q=0,image/webp;q=1', 'webp'];
        yield 'a wildcard does not imply avif' => ['image/webp,image/png,image/*;q=0.8', 'webp'];
        yield 'an image wildcard alone falls back' => ['image/*', 'jpeg'];
        yield 'a type that only starts with a format name is not that format' => ['image/avif-sequence', 'jpeg'];
    }

    public function testAnUnmatchedAcceptFallsBackToTheExplicitFallbackNotTheLastListElement(): void
    {
        self::assertSame('jpeg', new FormatNegotiator()->negotiate('text/html,*/*', GlideProvider::SUPPORTED_FORMATS, 'jpeg'));
    }

    public function testANullAcceptFallsBackToTheExplicitFallback(): void
    {
        self::assertSame('jpeg', new FormatNegotiator()->negotiate(null, GlideProvider::SUPPORTED_FORMATS, 'jpeg'));
    }

    public function testAClientAskingForHeicGetsTheFallbackSinceGdCannotEncodeIt(): void
    {
        self::assertSame('jpeg', new FormatNegotiator()->negotiate('image/heic,*/*', GlideProvider::SUPPORTED_FORMATS, 'jpeg'));
    }

    public function testTheFallbackIsHonouredIndependentlyOfListOrder(): void
    {
        self::assertSame('webp', new FormatNegotiator()->negotiate('text/html,*/*', ['avif', 'webp', 'jpeg'], 'webp'));
    }
}
