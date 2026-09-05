<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;

final class ImageTransformationTest extends TestCase
{
    public function testItKeepsTheGivenValues()
    {
        $t = new ImageTransformation('hero.jpg', width: 800, height: 450, fit: Fit::Cover, format: 'webp', quality: 80);

        self::assertSame('hero.jpg', $t->path);
        self::assertSame(800, $t->width);
        self::assertSame(450, $t->height);
        self::assertSame(Fit::Cover, $t->fit);
        self::assertSame('webp', $t->format);
        self::assertSame(80, $t->quality);
        self::assertSame([], $t->operations);
    }

    public function testItRejectsAnEmptyPath()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image path must not be empty.');

        new ImageTransformation('');
    }

    #[DataProvider('provideDotSegmentPaths')]
    public function testItRejectsADotSegmentPath(string $path)
    {
        $this->expectException(InvalidArgumentException::class);

        new ImageTransformation($path);
    }

    public static function provideDotSegmentPaths(): iterable
    {
        yield 'parent segment at the start' => ['../a.jpg'];
        yield 'parent segment in the middle' => ['a/../b.jpg'];
        yield 'current segment at the start' => ['./a.jpg'];
    }

    public function testItAcceptsAPathSegmentThatOnlyLooksLikeADotSegment()
    {
        $t = new ImageTransformation('a..b/c.jpg');

        self::assertSame('a..b/c.jpg', $t->path);
    }

    #[DataProvider('provideInvalidDimensions')]
    public function testItRejectsNonPositiveDimensions(?int $width, ?int $height)
    {
        $this->expectException(InvalidArgumentException::class);

        new ImageTransformation('hero.jpg', width: $width, height: $height);
    }

    public static function provideInvalidDimensions(): iterable
    {
        yield 'zero width' => [0, null];
        yield 'negative width' => [-1, null];
        yield 'zero height' => [null, 0];
    }

    public function testItRejectsAQualityOutsideOneToHundred()
    {
        $this->expectException(InvalidArgumentException::class);

        new ImageTransformation('hero.jpg', quality: 101);
    }
}
