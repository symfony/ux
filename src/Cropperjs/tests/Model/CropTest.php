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
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\UX\Cropperjs\Model\Crop;

/**
 * @internal
 */
class CropTest extends TestCase
{
    use ExpectDeprecationTrait;
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

    /**
     * @group legacy
     */
    public function testGetCroppedImageWithApplyRotationFalseTriggersDeprecation()
    {
        $crop = $this->createCrop();

        $this->expectDeprecation('Since symfony/ux-cropperjs 2.34: Not passing "true" to the "$applyRotation" argument of "Symfony\UX\Cropperjs\Model\Crop::getCroppedImage()" is deprecated. Rotation will be applied by default in Symfony UX 3.0.');

        $crop->getCroppedImage(applyRotation: false);
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

    /**
     * @group legacy
     */
    public function testGetCroppedThumbnailWithApplyRotationFalseTriggersDeprecation()
    {
        $crop = $this->createCrop();

        $this->expectDeprecation('Since symfony/ux-cropperjs 2.34: Not passing "true" to the "$applyRotation" argument of "Symfony\UX\Cropperjs\Model\Crop::getCroppedThumbnail()" is deprecated. Rotation will be applied by default in Symfony UX 3.0.');

        $crop->getCroppedThumbnail(200, 200, applyRotation: false);
    }

    public function testSetOptionsAcceptsCropperjsOutput()
    {
        $crop = new Crop(new ImageManager(), $this->testImagePath);
        $options = ['x' => 10.5, 'y' => 0, 'width' => 100.2, 'height' => 50, 'rotate' => -90, 'scaleX' => 1, 'scaleY' => -1];

        $crop->setOptions(json_encode($options));

        $this->assertSame($options, json_decode($crop->getOptions(), true));
    }

    public function testSetOptionsAcceptsNullDimensions()
    {
        $crop = new Crop(new ImageManager(), $this->testImagePath);

        $crop->setOptions('{"x":0,"y":0,"width":null,"height":null,"rotate":0}');

        $this->assertSame(['x' => 0, 'y' => 0, 'width' => null, 'height' => null, 'rotate' => 0], json_decode($crop->getOptions(), true));
    }

    public function testSetOptionsKeepsUnknownKeys()
    {
        $crop = new Crop(new ImageManager(), $this->testImagePath);

        $crop->setOptions('{"width":10,"height":10,"x":0,"y":0,"extra":"value"}');

        $this->assertSame('value', json_decode($crop->getOptions(), true)['extra']);
    }

    /**
     * @dataProvider invalidOptionsProvider
     */
    public function testSetOptionsRejectsInvalidOptions(string $options)
    {
        $crop = new Crop(new ImageManager(), $this->testImagePath);

        $this->expectException(\InvalidArgumentException::class);

        $crop->setOptions($options);
    }

    public static function invalidOptionsProvider(): iterable
    {
        yield 'invalid JSON' => ['{not json'];
        yield 'empty string' => [''];
        yield 'JSON null' => ['null'];
        yield 'JSON string' => ['"abc"'];
        yield 'JSON number' => ['42'];
        yield 'JSON boolean' => ['true'];
        yield 'JSON list' => ['[1,2]'];
        yield 'array width' => ['{"width":[1],"height":2}'];
        yield 'object height' => ['{"width":1,"height":{"a":1}}'];
        yield 'string width' => ['{"width":"x","height":2}'];
        yield 'numeric string width' => ['{"width":"10","height":2}'];
        yield 'boolean x' => ['{"width":1,"height":2,"x":true}'];
        yield 'string rotate' => ['{"width":1,"height":2,"rotate":"90"}'];
        yield 'infinite width' => ['{"width":1e999,"height":2}'];
        yield 'infinite scaleX' => ['{"scaleX":-1e999}'];
    }
}
