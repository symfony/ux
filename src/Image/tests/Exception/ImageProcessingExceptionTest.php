<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\ExceptionInterface;
use Symfony\UX\Image\Exception\ImageProcessingException;

final class ImageProcessingExceptionTest extends TestCase
{
    #[Test]
    public function itExtendsExceptionInterface(): void
    {
        $exception = new ImageProcessingException('test');

        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function processingFailedWithoutReason(): void
    {
        $exception = ImageProcessingException::processingFailed('resize');

        self::assertSame('Image processing operation "resize" failed', $exception->getMessage());
    }

    #[Test]
    public function processingFailedWithReason(): void
    {
        $exception = ImageProcessingException::processingFailed('crop', 'memory limit exceeded');

        self::assertSame('Image processing operation "crop" failed: memory limit exceeded', $exception->getMessage());
    }

    #[Test]
    public function processingFailedWithEmptyReason(): void
    {
        $exception = ImageProcessingException::processingFailed('rotate', '');

        self::assertSame('Image processing operation "rotate" failed', $exception->getMessage());
    }

    #[Test]
    public function unsupportedFormat(): void
    {
        $exception = ImageProcessingException::unsupportedFormat('bmp');

        self::assertSame('Unsupported image format: bmp', $exception->getMessage());
    }

    #[Test]
    public function invalidDimensions(): void
    {
        $exception = ImageProcessingException::invalidDimensions(0, -5);

        self::assertSame('Invalid image dimensions: 0x-5', $exception->getMessage());
    }

    #[Test]
    public function invalidDimensionsWithPositiveValues(): void
    {
        $exception = ImageProcessingException::invalidDimensions(1920, 1080);

        self::assertSame('Invalid image dimensions: 1920x1080', $exception->getMessage());
    }
}
