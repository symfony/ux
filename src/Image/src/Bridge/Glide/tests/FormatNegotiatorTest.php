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
    public function testFormatNegotiation(?string $accept, string $expected)
    {
        self::assertSame($expected, new FormatNegotiator()->negotiate($accept, ['avif', 'webp', 'jpeg'], 'jpeg'));
    }

    public static function provideAcceptHeaders(): iterable
    {
        yield 'avif preferred' => ['image/avif,image/webp,*/*', 'avif'];
        yield 'webp only' => ['image/webp,*/*', 'webp'];
        yield 'neither' => ['text/html,*/*', 'jpeg'];
        yield 'no header' => [null, 'jpeg'];
    }

    public function testAnUnmatchedAcceptFallsBackToTheExplicitFallbackNotTheLastListElement()
    {
        self::assertSame('jpeg', new FormatNegotiator()->negotiate('text/html,*/*', GlideProvider::SUPPORTED_FORMATS, 'jpeg'));
    }

    public function testANullAcceptFallsBackToTheExplicitFallback()
    {
        self::assertSame('jpeg', new FormatNegotiator()->negotiate(null, GlideProvider::SUPPORTED_FORMATS, 'jpeg'));
    }

    public function testHeicIsReturnedOnlyWhenTheClientActuallyAsksForIt()
    {
        self::assertSame('heic', new FormatNegotiator()->negotiate('image/heic,*/*', GlideProvider::SUPPORTED_FORMATS, 'jpeg'));
    }

    public function testTheFallbackIsHonouredIndependentlyOfListOrder()
    {
        self::assertSame('webp', new FormatNegotiator()->negotiate('text/html,*/*', ['avif', 'webp', 'jpeg'], 'webp'));
    }
}
