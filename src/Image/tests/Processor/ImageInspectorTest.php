<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Processor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\InspectedImage;
use Symfony\UX\Image\Processor\ImageInspector;

#[CoversClass(ImageInspector::class)]
#[CoversClass(InspectedImage::class)]
final class ImageInspectorTest extends TestCase
{
    private ImageInspector $inspector;

    protected function setUp(): void
    {
        $this->inspector = new ImageInspector();
    }

    public function testInspectNonExistentFile(): void
    {
        $result = $this->inspector->inspect('/nonexistent/path/image.jpg');

        self::assertNull($result['width']);
        self::assertNull($result['height']);
        self::assertNull($result['mime']);
        self::assertNull($result['format']);
    }

    public function testStrictInspectionRejectsMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $this->inspector->inspectImage('/missing/image.jpg');
    }

    public function testInspectedImageRejectsInvalidDimensions(): void
    {
        $this->expectException(\RuntimeException::class);

        new InspectedImage('png', 'image/png', 0, 1, 1);
    }

    #[\PHPUnit\Framework\Attributes\RequiresPhpExtension('gd')]
    public function testInspectJpegFile(): void
    {
        $tmpFile = sys_get_temp_dir().'/ux_image_test_'.uniqid().'.jpg';
        $img = imagecreatetruecolor(120, 80);
        imagejpeg($img, $tmpFile);
        unset($img);

        $result = $this->inspector->inspect($tmpFile);

        self::assertSame(120, $result['width']);
        self::assertSame(80, $result['height']);
        self::assertSame('image/jpeg', $result['mime']);
        self::assertSame('jpeg', $result['format']);

        @unlink($tmpFile);
    }

    #[\PHPUnit\Framework\Attributes\RequiresPhpExtension('gd')]
    public function testStrictInspectionExposesTheTrustedMetadataContractThroughGetters(): void
    {
        $tmpFile = sys_get_temp_dir().'/ux_image_strict_'.uniqid().'.jpg';
        $img = imagecreatetruecolor(12, 8);
        imagejpeg($img, $tmpFile);
        unset($img);

        $inspected = $this->inspector->inspectImage($tmpFile);

        self::assertSame('jpeg', $inspected->getFormat());
        self::assertSame('image/jpeg', $inspected->getMimeType());
        self::assertSame(12, $inspected->getWidth());
        self::assertSame(8, $inspected->getHeight());
        self::assertGreaterThan(0, $inspected->getBytes());
        self::assertSame(96, $inspected->getPixelCount());

        @unlink($tmpFile);
    }

    #[\PHPUnit\Framework\Attributes\RequiresPhpExtension('gd')]
    public function testInspectPngFile(): void
    {
        $tmpFile = sys_get_temp_dir().'/ux_image_test_'.uniqid().'.png';
        $img = imagecreatetruecolor(200, 150);
        imagepng($img, $tmpFile);
        unset($img);

        $result = $this->inspector->inspect($tmpFile);

        self::assertSame(200, $result['width']);
        self::assertSame(150, $result['height']);
        self::assertSame('image/png', $result['mime']);
        self::assertSame('png', $result['format']);

        @unlink($tmpFile);
    }

    public function testResolveFormatMapping(): void
    {
        // Create minimal valid images for each format to test the mime-to-format mapping.
        // We test the format mapping indirectly through inspect() since resolveFormat is private.
        $mappings = [
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
        ];

        // Test with a JPEG
        if (\extension_loaded('gd')) {
            $jpegFile = sys_get_temp_dir().'/ux_image_format_'.uniqid().'.jpg';
            $img = imagecreatetruecolor(1, 1);
            imagejpeg($img, $jpegFile);
            unset($img);

            $result = $this->inspector->inspect($jpegFile);
            self::assertSame('jpeg', $result['format']);

            @unlink($jpegFile);

            $pngFile = sys_get_temp_dir().'/ux_image_format_'.uniqid().'.png';
            $img = imagecreatetruecolor(1, 1);
            imagepng($img, $pngFile);
            unset($img);

            $result = $this->inspector->inspect($pngFile);
            self::assertSame('png', $result['format']);

            @unlink($pngFile);
        }
    }

    public function testInspectNonImageFile(): void
    {
        $tmpFile = sys_get_temp_dir().'/ux_image_test_'.uniqid().'.txt';
        file_put_contents($tmpFile, 'this is not an image');

        $result = $this->inspector->inspect($tmpFile);

        // getimagesize returns false for non-image files
        self::assertNull($result['width']);
        self::assertNull($result['height']);
        self::assertNull($result['format']);

        @unlink($tmpFile);
    }

    public function testInspectWithFileObject(): void
    {
        $tmpFile = sys_get_temp_dir().'/ux_image_file_obj_'.uniqid().'.txt';
        file_put_contents($tmpFile, 'not an image');

        $file = new \Symfony\Component\HttpFoundation\File\File($tmpFile);
        $result = $this->inspector->inspect($file);

        self::assertNull($result['format']);

        @unlink($tmpFile);
    }

    #[\PHPUnit\Framework\Attributes\RequiresPhpExtension('gd')]
    public function testInspectWebpFile(): void
    {
        if (!\function_exists('imagewebp')) {
            self::markTestSkipped('WebP support not available in GD.');
        }

        $tmpFile = sys_get_temp_dir().'/ux_image_test_'.uniqid().'.webp';
        $img = imagecreatetruecolor(50, 50);
        imagewebp($img, $tmpFile);
        unset($img);

        $result = $this->inspector->inspect($tmpFile);

        self::assertSame(50, $result['width']);
        self::assertSame(50, $result['height']);
        self::assertSame('image/webp', $result['mime']);
        self::assertSame('webp', $result['format']);

        @unlink($tmpFile);
    }

    #[\PHPUnit\Framework\Attributes\RequiresPhpExtension('gd')]
    public function testInspectGifFile(): void
    {
        $tmpFile = sys_get_temp_dir().'/ux_image_test_'.uniqid().'.gif';
        $img = imagecreatetruecolor(30, 20);
        imagegif($img, $tmpFile);
        unset($img);

        $result = $this->inspector->inspect($tmpFile);

        self::assertSame(30, $result['width']);
        self::assertSame(20, $result['height']);
        self::assertSame('image/gif', $result['mime']);
        self::assertSame('gif', $result['format']);

        @unlink($tmpFile);
    }
}
