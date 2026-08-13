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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\ImageSource;
use Symfony\UX\Image\Storage\StoragePath;

#[CoversClass(ImageSource::class)]
final class ImageSourceTest extends TestCase
{
    public function testCreatesGeneratedSourceFromStoragePath(): void
    {
        $variant = ImageSource::generated(
            name: 'card',
            path: new StoragePath('products/photo_card.webp'),
            format: 'webp',
            mimeType: 'image/webp',
            width: 600,
            height: 400,
            media: '(min-width: 40rem)',
            density: '2x',
            mode: 'crop',
            quality: 86,
            position: '30% 60%',
        );

        self::assertSame([
            'path' => '/products/photo_card.webp',
            'width' => 600,
            'height' => 400,
            'density' => '2x',
            'media' => '(min-width: 40rem)',
            'name' => 'card',
            'format' => 'webp',
            'mimeType' => 'image/webp',
            'mode' => 'crop',
            'quality' => 86,
            'position' => '30% 60%',
        ], $variant->toArray());
        self::assertSame([
            'name' => 'card',
            'path' => '/products/photo_card.webp',
            'format' => 'webp',
            'mimeType' => 'image/webp',
            'width' => 600,
            'height' => 400,
            'mode' => 'crop',
            'quality' => 86,
            'position' => '30% 60%',
            'media' => '(min-width: 40rem)',
            'density' => '2x',
        ], $variant->toGeneratedArray());
    }

    public function testGeneratedSourceRequiresCompleteMetadata(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ImageSource::generated('', new StoragePath('photo.webp'), 'webp', 'image/webp', 600, 400);
    }

    public function testGenericSourceCannotSerializeAsGeneratedSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ImageSource('/photo.webp')->toGeneratedArray();
    }

    public function testConstructor(): void
    {
        $variant = new ImageSource('/img/photo.webp', 640, 480, '2x', '(max-width: 768px)', 'card', 'webp', 'image/webp', 'crop', 82, 'top');

        self::assertSame('/img/photo.webp', $variant->path);
        self::assertSame(640, $variant->width);
        self::assertSame(480, $variant->height);
        self::assertSame('2x', $variant->density);
        self::assertSame('(max-width: 768px)', $variant->media);
        self::assertSame('/img/photo.webp', $variant->getPath());
        self::assertSame(640, $variant->getWidth());
        self::assertSame(480, $variant->getHeight());
        self::assertSame('2x', $variant->getDensity());
        self::assertSame('(max-width: 768px)', $variant->getMedia());
        self::assertSame('card', $variant->getName());
        self::assertSame('webp', $variant->getFormat());
        self::assertSame('image/webp', $variant->getMimeType());
        self::assertSame('crop', $variant->getMode());
        self::assertSame(82, $variant->getQuality());
        self::assertSame('top', $variant->getPosition());
    }

    public function testConstructorDefaults(): void
    {
        $variant = new ImageSource('/img/photo.webp');

        self::assertNull($variant->width);
        self::assertNull($variant->height);
        self::assertNull($variant->density);
        self::assertNull($variant->media);
    }

    public function testFromArray(): void
    {
        $variant = ImageSource::fromArray([
            'path' => '/img/photo.webp',
            'width' => 640,
            'height' => 480,
            'density' => '1x',
            'media' => '(max-width: 768px)',
        ]);

        self::assertSame('/img/photo.webp', $variant->path);
        self::assertSame(640, $variant->width);
        self::assertSame(480, $variant->height);
        self::assertSame('1x', $variant->density);
        self::assertSame('(max-width: 768px)', $variant->media);
    }

    public function testFromArrayMinimal(): void
    {
        $variant = ImageSource::fromArray(['path' => '/img/photo.webp']);

        self::assertSame('/img/photo.webp', $variant->path);
        self::assertNull($variant->width);
        self::assertNull($variant->height);
        self::assertNull($variant->density);
        self::assertNull($variant->media);
    }

    public function testToArray(): void
    {
        $variant = new ImageSource('/img/photo.webp', 640, 480, '2x', '(max-width: 768px)');

        self::assertSame([
            'path' => '/img/photo.webp',
            'width' => 640,
            'height' => 480,
            'density' => '2x',
            'media' => '(max-width: 768px)',
        ], $variant->toArray());
    }

    public function testRoundTrip(): void
    {
        $data = [
            'path' => '/img/photo.webp',
            'width' => 1024,
            'height' => 768,
            'density' => null,
            'media' => null,
        ];

        $variant = ImageSource::fromArray($data);

        self::assertSame($data, $variant->toArray());
    }

    #[DataProvider('invalidConstructorValues')]
    public function testRejectsInvalidConstructorValues(array $arguments): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ImageSource(...$arguments);
    }

    public static function invalidConstructorValues(): iterable
    {
        yield 'empty path' => [['path' => '  ']];
        yield 'empty name' => [['path' => '/image.jpg', 'name' => '  ']];
        yield 'invalid dimensions' => [['path' => '/image.jpg', 'width' => 0]];
        yield 'invalid mode' => [['path' => '/image.jpg', 'mode' => 'stretch']];
        yield 'invalid quality' => [['path' => '/image.jpg', 'quality' => 101]];
        yield 'empty position' => [['path' => '/image.jpg', 'position' => '  ']];
        yield 'invalid density' => [['path' => '/image.jpg', 'density' => 'retina']];
        yield 'empty media' => [['path' => '/image.jpg', 'media' => '  ']];
    }

    #[DataProvider('invalidSerializedValues')]
    public function testRejectsInvalidSerializedValues(array $data): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ImageSource::fromArray($data);
    }

    public static function invalidSerializedValues(): iterable
    {
        yield 'missing path' => [[]];
        yield 'string metadata' => [['path' => '/image.jpg', 'format' => 42]];
        yield 'integer metadata' => [['path' => '/image.jpg', 'width' => '640']];
    }
}
