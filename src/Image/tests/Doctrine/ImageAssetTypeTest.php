<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Doctrine\ImageAssetType;
use Symfony\UX\Image\ImageAsset;

#[CoversClass(ImageAssetType::class)]
final class ImageAssetTypeTest extends TestCase
{
    private ImageAssetType $type;
    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        $this->type = new ImageAssetType();
        $this->platform = $this->createStub(AbstractPlatform::class);
    }

    public function testConvertToPHPValueWithNull()
    {
        $result = $this->type->convertToPHPValue(null, $this->platform);
        $this->assertNull($result);

        $result = $this->type->convertToPHPValue('', $this->platform);
        $this->assertNull($result);
    }

    public function testConvertToPHPValueWithValidData()
    {
        $data = [
            'schemaVersion' => ImageAsset::SCHEMA_VERSION,
            'storageName' => 'default_public',
            'path' => '/uploads/images/test.jpg',
            'originalFilename' => 'test.jpg',
            'mimeType' => 'image/jpeg',
            'width' => 800,
            'height' => 600,
            'variants' => [
                'webp' => [[
                    'name' => 'thumbnail',
                    'path' => '/uploads/images/test_thumbnail.webp',
                    'format' => 'webp',
                    'mimeType' => 'image/webp',
                    'width' => 150,
                    'height' => 150,
                    'mode' => 'crop',
                    'quality' => 80,
                    'position' => 'center',
                    'media' => null,
                    'density' => null,
                ]],
            ],
        ];

        $json = json_encode($data, \JSON_THROW_ON_ERROR);

        $result = $this->type->convertToPHPValue($json, $this->platform);

        $this->assertInstanceOf(ImageAsset::class, $result);
        $this->assertSame('default_public', $result->storageName);
        $this->assertSame('/uploads/images/test.jpg', $result->path);
    }

    public function testConvertToDatabaseValueWithNull()
    {
        $result = $this->type->convertToDatabaseValue(null, $this->platform);
        $this->assertNull($result);
    }

    public function testConvertToDatabaseValueWithImageAsset()
    {
        $imageAsset = new ImageAsset(
            storageName: 'default_public',
            path: '/uploads/images/test.jpg',
            originalFilename: 'test.jpg',
            mimeType: 'image/jpeg',
            width: 800,
            height: 600
        );

        $result = $this->type->convertToDatabaseValue($imageAsset, $this->platform);

        $this->assertSame(json_encode($imageAsset->toArray(), \JSON_THROW_ON_ERROR), $result);
    }

    public function testConvertToDatabaseValueWithInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected ImageAsset instance, got string');

        $this->type->convertToDatabaseValue('invalid', $this->platform);
    }

    public function testConvertToPHPValueWithNonArrayJsonFailsExplicitly()
    {
        $this->expectException(ValueNotConvertible::class);

        $this->type->convertToPHPValue('"just a string"', $this->platform);
    }

    public function testConvertToPHPValueWrapsInvalidImageAsset()
    {
        $this->expectException(ValueNotConvertible::class);

        $this->type->convertToPHPValue('{"schemaVersion":1,"storageName":"","path":""}', $this->platform);
    }
}
