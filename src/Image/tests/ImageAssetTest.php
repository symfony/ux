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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\ImageAsset;

#[CoversClass(ImageAsset::class)]
final class ImageAssetTest extends TestCase
{
    public function testCreateImageAsset()
    {
        $asset = new ImageAsset(
            storageName: 'default_public',
            path: '/uploads/images/test.jpg',
            originalFilename: 'test.jpg',
            mimeType: 'image/jpeg',
            width: 800,
            height: 600,
            profile: 'product',
            profileRevision: 'revision',
        );

        self::assertSame('default_public', $asset->storageName);
        self::assertSame('/uploads/images/test.jpg', $asset->path);
        self::assertSame('test.jpg', $asset->originalFilename);
        self::assertSame('image/jpeg', $asset->mimeType);
        self::assertSame(800, $asset->width);
        self::assertSame(600, $asset->height);
        self::assertSame([], $asset->variants);
        self::assertSame('default_public', $asset->getStorageName());
        self::assertSame('/uploads/images/test.jpg', $asset->getPath());
        self::assertSame('test.jpg', $asset->getOriginalFilename());
        self::assertSame('image/jpeg', $asset->getMimeType());
        self::assertSame(800, $asset->getWidth());
        self::assertSame(600, $asset->getHeight());
        self::assertSame([], $asset->getVariants());
        self::assertSame(ImageAsset::SCHEMA_VERSION, $asset->getSchemaVersion());
        self::assertSame('product', $asset->getProfile());
        self::assertSame('revision', $asset->getProfileRevision());
    }

    public function testRoundTripsTheCanonicalDurableShape()
    {
        $original = new ImageAsset(
            storageName: 'default_public',
            path: '/uploads/images/test.jpg',
            originalFilename: 'test.jpg',
            mimeType: 'image/jpeg',
            width: 800,
            height: 600,
            variants: [
                'webp' => [self::variant('/uploads/images/test_card.webp', 'card', 'webp', 600, 400)],
                'jpeg' => [self::variant('/uploads/images/test_card.jpeg', 'card', 'jpeg', 600, 400)],
            ],
            profile: 'product',
            profileRevision: 'revision',
        );

        $serialized = $original->toArray();
        $recreated = ImageAsset::fromArray($serialized);

        self::assertEquals($original, $recreated);
        self::assertSame(ImageAsset::SCHEMA_VERSION, $serialized['schemaVersion']);
    }

    public function testVariantMethodsReadCanonicalShape()
    {
        $asset = new ImageAsset('default_public', '/photo.jpg', variants: [
            'webp' => [
                self::variant('/photo_thumbnail.webp', 'thumbnail', 'webp', 150, 100),
                self::variant('/photo_large.webp', 'large', 'webp', 1200, 800),
            ],
            'jpeg' => [
                self::variant('/photo_thumbnail.jpeg', 'thumbnail', 'jpeg', 150, 100),
            ],
        ]);

        self::assertTrue($asset->hasVariant('thumbnail'));
        self::assertTrue($asset->hasVariant('large'));
        self::assertFalse($asset->hasVariant('missing'));
        self::assertSame('/photo_thumbnail.webp', $asset->getVariant('thumbnail')['path'] ?? null);
        self::assertNull($asset->getVariant('missing'));
    }

    public function testFormatsPrimaryVariantAndSrcset()
    {
        $asset = new ImageAsset('default', '/photo.jpg', variants: [
            'png' => [self::variant('/photo.png', 'card', 'png', 600, 400)],
            'avif' => [
                self::variant('/photo_small.avif', 'small', 'avif', 600, 400),
                self::variant('/photo_large.avif', 'large', 'avif', 1200, 800),
            ],
        ]);

        self::assertSame(['png', 'avif'], $asset->getAvailableFormats());
        self::assertSame('avif', $asset->getDefaultFormat());
        self::assertSame('/photo_small.avif', $asset->getPrimaryVariantForFormat('avif')['path'] ?? null);
        self::assertNull($asset->getPrimaryVariantForFormat('webp'));
    }

    public function testArtDirectionUsesMediaOnEachCanonicalVariant()
    {
        $mobile = self::variant('/mobile.webp', 'mobile', 'webp', 640, 640);
        $mobile['media'] = '(max-width: 40rem)';
        $desktop = self::variant('/desktop.webp', 'desktop', 'webp', 1280, 720);
        $desktop['media'] = '(min-width: 40.001rem)';
        $asset = new ImageAsset('default', '/photo.jpg', variants: ['webp' => [$mobile, $desktop]]);

        self::assertTrue($asset->getImageSourceSet()->isMultiRatio());
        self::assertCount(2, $asset->getImageSourceSet()->getMultiRatioGroups());
    }

    public function testFilePathsAreUnique()
    {
        $asset = new ImageAsset('default', '/original.jpeg', variants: [
            'webp' => [
                self::variant('/small.webp', 'small', 'webp', 320, 200),
                self::variant('/large.webp', 'large', 'webp', 1280, 800),
            ],
            'jpeg' => [
                self::variant('/original.jpeg', 'small', 'jpeg', 320, 200),
            ],
        ]);

        self::assertSame(['/original.jpeg', '/small.webp', '/large.webp'], $asset->getFilePaths());
    }

    public function testEmptyVariantsHaveNoDerivedMetadata()
    {
        $asset = new ImageAsset('default', '/photo.jpg');

        self::assertSame([], $asset->getAvailableFormats());
        self::assertNull($asset->getDefaultFormat());
        self::assertNull($asset->getPrimaryVariantForFormat('webp'));
        self::assertTrue($asset->getImageSourceSet()->isEmpty());
    }

    public function testRejectsStorageNameTraversal()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid image storage name');

        new ImageAsset('../outside', '/image.jpeg');
    }

    public function testRejectsUnknownSchemaVersion()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('schema version');

        ImageAsset::fromArray([
            'schemaVersion' => 99,
            'storageName' => 'default',
            'path' => '/photo.jpeg',
        ]);
    }

    public function testRequiresSchemaVersionWhenDeserializing()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('schemaVersion must be provided');

        ImageAsset::fromArray([
            'storageName' => 'default',
            'path' => '/photo.jpeg',
        ]);
    }

    public function testRejectsLegacyTopLevelArtDirectionShape()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('format => list<variant>');

        new ImageAsset('default', '/photo.jpeg', variants: [
            ['media' => '(max-width: 40rem)', 'formats' => ['webp' => [self::variant('/mobile.webp', 'mobile', 'webp', 640, 640)]]],
        ]);
    }

    public function testRejectsLegacyVariantNameShape()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty list');

        new ImageAsset('default', '/photo.jpeg', variants: [
            'thumbnail' => ['webp' => self::variant('/thumbnail.webp', 'thumbnail', 'webp', 300, 300)],
        ]);
    }

    public function testRejectsMalformedNestedVariantInsteadOfSkippingIt()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('webp[1]');

        new ImageAsset('default', '/photo.jpeg', variants: [
            'webp' => [self::variant('/valid.webp', 'valid', 'webp', 640, 480), 'invalid'],
        ]);
    }

    public function testRejectsInvalidScalarMetadataDuringDeserialization()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('dimensions');

        ImageAsset::fromArray([
            'schemaVersion' => ImageAsset::SCHEMA_VERSION,
            'storageName' => 'default',
            'path' => '/photo.jpeg',
            'width' => '800',
        ]);
    }

    public function testAbsoluteUrlAndUnknownFormatRemainSupported()
    {
        $asset = new ImageAsset('remote', 'https://example.com/photo.bmp', variants: [
            'bmp' => [self::variant('/photo.bmp', 'original', 'bmp', 100, 50)],
        ]);

        self::assertSame('bmp', $asset->getDefaultFormat());
    }

    public function testRejectsNonPositiveDimensions()
    {
        $this->expectException(\InvalidArgumentException::class);

        new ImageAsset('default', '/photo.jpg', width: 0);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidSerializedMetadata')]
    public function testRejectsInvalidSerializedMetadata(array $data)
    {
        $this->expectException(\InvalidArgumentException::class);

        ImageAsset::fromArray($data + ['schemaVersion' => ImageAsset::SCHEMA_VERSION]);
    }

    public static function invalidSerializedMetadata(): iterable
    {
        yield 'variants' => [['storageName' => 'default', 'path' => '/photo.jpg', 'variants' => 'invalid']];
        yield 'profile' => [['storageName' => 'default', 'path' => '/photo.jpg', 'profile' => 42]];
        yield 'identity' => [['storageName' => '', 'path' => '/photo.jpg']];
        yield 'filename' => [['storageName' => 'default', 'path' => '/photo.jpg', 'originalFilename' => 42]];
    }

    /**
     * @return array<string, int|string|null>
     */
    private static function variant(string $path, string $name, string $format, int $width, int $height): array
    {
        return [
            'name' => $name,
            'path' => $path,
            'format' => $format,
            'mimeType' => 'image/'.$format,
            'width' => $width,
            'height' => $height,
            'mode' => 'fit',
            'quality' => 80,
            'position' => 'center',
            'media' => null,
            'density' => null,
        ];
    }
}
