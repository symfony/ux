<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Cropperjs\Tests\Model;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Cropperjs\Model\Crop;

/**
 * @internal
 */
class CropTest extends TestCase
{
    private string $testImagePath;

    protected function setUp(): void
    {
        $this->testImagePath = tempnam(sys_get_temp_dir(), 'crop_test_').'.jpg';
        ob_start();
        imagejpeg(imagecreatetruecolor(200, 100), $this->testImagePath);
        ob_end_clean();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testImagePath)) {
            unlink($this->testImagePath);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideDrivers(): iterable
    {
        yield 'gd' => [GdDriver::class];

        if (\extension_loaded('imagick')) {
            yield 'imagick' => [ImagickDriver::class];
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createCrop(string $driver, array $options = [], ?string $imagePath = null): Crop
    {
        $crop = new Crop(ImageManager::usingDriver($driver), $imagePath ?? $this->testImagePath);

        $crop->setOptions(json_encode($options + [
            'x' => 0,
            'y' => 0,
            'width' => null,
            'height' => null,
            'rotate' => 0,
        ]));

        return $crop;
    }

    #[DataProvider('provideDrivers')]
    public function testGetCroppedImageWithRotation(string $driver)
    {
        $result = $this->createCrop($driver, ['rotate' => 90])->getCroppedImage();

        $image = imagecreatefromstring($result);
        $this->assertSame(100, imagesx($image));
        $this->assertSame(200, imagesy($image));
    }

    #[DataProvider('provideDrivers')]
    public function testGetCroppedImageWithoutRotation(string $driver)
    {
        $result = $this->createCrop($driver)->getCroppedImage();

        $image = imagecreatefromstring($result);
        $this->assertSame(200, imagesx($image));
        $this->assertSame(100, imagesy($image));
    }

    #[DataProvider('provideDrivers')]
    public function testGetCroppedImageCropsTheRequestedRegion(string $driver)
    {
        // Source: left half red, right half blue (split at x = 100)
        $source = $this->createTwoColorImage();

        try {
            // The 50x40 region at x=120 sits entirely in the blue (right) half
            $result = $this->createCrop($driver, [
                'x' => 120,
                'y' => 10,
                'width' => 50,
                'height' => 40,
            ], $source)->getCroppedImage('png');
        } finally {
            unlink($source);
        }

        $image = imagecreatefromstring($result);
        $this->assertSame(50, imagesx($image), 'Cropped width should match the requested region width');
        $this->assertSame(40, imagesy($image), 'Cropped height should match the requested region height');

        // Locks the crop(width, height, x, y) argument order: a swapped x/y would land in the red half
        $color = imagecolorat($image, 25, 20);
        $this->assertLessThan(80, ($color >> 16) & 0xFF, 'Red channel should be low (region is in the blue half)');
        $this->assertGreaterThan(200, $color & 0xFF, 'Blue channel should be high (region is in the blue half)');
    }

    #[DataProvider('provideDrivers')]
    public function testGetCroppedImageRespectsMaxSize(string $driver)
    {
        $crop = $this->createCrop($driver);
        $crop->setCroppedMaxSize(100, 100);

        $image = imagecreatefromstring($crop->getCroppedImage());
        $this->assertSame(100, imagesx($image));
        $this->assertSame(50, imagesy($image));
    }

    #[DataProvider('provideDrivers')]
    public function testGetCroppedImageEncodesTheRequestedFormat(string $driver)
    {
        $result = $this->createCrop($driver)->getCroppedImage('png');

        $this->assertSame("\x89PNG", substr($result, 0, 4));
        $this->assertNotFalse(imagecreatefromstring($result));
    }

    #[DataProvider('provideDrivers')]
    public function testGetCroppedThumbnailWithRotation(string $driver)
    {
        $result = $this->createCrop($driver, ['rotate' => 90])->getCroppedThumbnail(200, 200);

        $image = imagecreatefromstring($result);
        $this->assertSame(100, imagesx($image));
        $this->assertSame(200, imagesy($image));
    }

    #[DataProvider('provideDrivers')]
    public function testGetCroppedThumbnailWithoutRotation(string $driver)
    {
        $result = $this->createCrop($driver)->getCroppedThumbnail(200, 200);

        $image = imagecreatefromstring($result);
        $this->assertSame(200, imagesx($image));
        $this->assertSame(100, imagesy($image));
    }

    #[DataProvider('provideDrivers')]
    public function testGetCroppedThumbnailDownscales(string $driver)
    {
        $result = $this->createCrop($driver)->getCroppedThumbnail(50, 50);

        $image = imagecreatefromstring($result);
        $this->assertSame(50, imagesx($image));
        $this->assertSame(25, imagesy($image));
    }

    private function createTwoColorImage(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'crop_test_two_color_').'.png';

        $image = imagecreatetruecolor(200, 100);
        imagefilledrectangle($image, 0, 0, 99, 99, imagecolorallocate($image, 255, 0, 0));
        imagefilledrectangle($image, 100, 0, 199, 99, imagecolorallocate($image, 0, 0, 255));
        imagepng($image, $path);

        return $path;
    }
}
