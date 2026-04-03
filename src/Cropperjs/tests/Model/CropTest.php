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

use Intervention\Image\ImageManager;
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

    private function createCrop(int $rotate = 0): Crop
    {
        $imageManager = new ImageManager();
        $crop = new Crop($imageManager, $this->testImagePath);

        $crop->setOptions(json_encode([
            'width' => null,
            'height' => null,
            'rotate' => $rotate,
        ]));

        return $crop;
    }

    public function testGetCroppedImageWithRotation()
    {
        $crop = $this->createCrop(rotate: 90);

        $result = $crop->getCroppedImage(applyRotation: true);

        $image = imagecreatefromstring($result);
        $this->assertSame(100, imagesx($image));
        $this->assertSame(200, imagesy($image));
    }

    public function testGetCroppedImageWithoutRotation()
    {
        $crop = $this->createCrop(rotate: 0);

        $result = $crop->getCroppedImage(applyRotation: true);

        $image = imagecreatefromstring($result);
        $this->assertSame(200, imagesx($image));
        $this->assertSame(100, imagesy($image));
    }

    public function testGetCroppedThumbnailWithRotation()
    {
        $crop = $this->createCrop(rotate: 90);

        $result = $crop->getCroppedThumbnail(200, 200, applyRotation: true);

        $image = imagecreatefromstring($result);
        $this->assertSame(100, imagesx($image));
        $this->assertSame(200, imagesy($image));
    }

    public function testGetCroppedThumbnailWithoutRotation()
    {
        $crop = $this->createCrop(rotate: 0);

        $result = $crop->getCroppedThumbnail(200, 200, applyRotation: true);

        $image = imagecreatefromstring($result);
        $this->assertSame(200, imagesx($image));
        $this->assertSame(100, imagesy($image));
    }
}
