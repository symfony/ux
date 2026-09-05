<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\UrlGenerator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\UrlGenerator\CachedUrlGenerator;
use Symfony\UX\Image\UrlGenerator\UrlGeneratorInterface;

#[CoversClass(CachedUrlGenerator::class)]
final class CachedUrlGeneratorTest extends TestCase
{
    public function testGenerateAssetUrlCachesResult()
    {
        $decoratedGenerator = $this->createMock(UrlGeneratorInterface::class);
        $decoratedGenerator->expects($this->once())
            ->method('generateAssetUrl')
            ->willReturn('https://example.com/image.jpg');

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with('https://example.com/image.jpg');
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);
        $cache->expects($this->once())->method('save')->with($cacheItem);

        $generator = new CachedUrlGenerator($decoratedGenerator, $cache, 3600);
        $asset = new ImageAsset('default', 'image.jpg');

        $url = $generator->generateAssetUrl($asset);

        $this->assertSame('https://example.com/image.jpg', $url);
    }

    public function testGenerateAssetUrlReturnsFromCache()
    {
        $decoratedGenerator = $this->createMock(UrlGeneratorInterface::class);
        $decoratedGenerator->expects($this->never())->method('generateAssetUrl');

        $cacheItem = $this->createStub(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn('https://example.com/cached-image.jpg');

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);
        $cache->expects($this->never())->method('save');

        $generator = new CachedUrlGenerator($decoratedGenerator, $cache, 3600);
        $asset = new ImageAsset('default', 'image.jpg');

        $url = $generator->generateAssetUrl($asset);

        $this->assertSame('https://example.com/cached-image.jpg', $url);
    }

    public function testGenerateVariantUrlCachesResult()
    {
        $decoratedGenerator = $this->createMock(UrlGeneratorInterface::class);
        $decoratedGenerator->expects($this->once())
            ->method('generateVariantUrl')
            ->willReturn('https://example.com/variant.jpg');

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with('https://example.com/variant.jpg');
        $cacheItem->expects($this->once())->method('expiresAfter')->with(7200);

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);
        $cache->expects($this->once())->method('save')->with($cacheItem);

        $generator = new CachedUrlGenerator($decoratedGenerator, $cache, 7200);
        $asset = new ImageAsset('default', 'image.jpg');
        $variant = ['path' => 'variant.jpg', 'width' => 400];

        $url = $generator->generateVariantUrl($asset, $variant);

        $this->assertSame('https://example.com/variant.jpg', $url);
    }

    public function testGenerateVariantUrlReturnsFromCache()
    {
        $decoratedGenerator = $this->createMock(UrlGeneratorInterface::class);
        $decoratedGenerator->expects($this->never())->method('generateVariantUrl');

        $cacheItem = $this->createStub(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn('https://example.com/cached-variant.jpg');

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);
        $cache->expects($this->never())->method('save');

        $generator = new CachedUrlGenerator($decoratedGenerator, $cache, 3600);
        $asset = new ImageAsset('default', 'image.jpg');
        $variant = ['path' => 'variant.jpg', 'width' => 400];

        $url = $generator->generateVariantUrl($asset, $variant);

        $this->assertSame('https://example.com/cached-variant.jpg', $url);
    }

    public function testVariantCacheKeyIncludesTheCompleteTransformation()
    {
        $decoratedGenerator = $this->createMock(UrlGeneratorInterface::class);
        $decoratedGenerator->expects($this->exactly(2))
            ->method('generateVariantUrl')
            ->willReturnCallback(static fn (ImageAsset $asset, array $variant): string => '/quality-'.$variant['quality']);

        $keys = [];
        $cache = $this->createStub(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturnCallback(function (string $key) use (&$keys): CacheItemInterface {
            $keys[] = $key;
            $item = $this->createStub(CacheItemInterface::class);
            $item->method('isHit')->willReturn(false);
            $item->method('set')->willReturnSelf();
            $item->method('expiresAfter')->willReturnSelf();

            return $item;
        });

        $generator = new CachedUrlGenerator($decoratedGenerator, $cache);
        $asset = new ImageAsset('default', 'image.jpg');

        self::assertSame('/quality-20', $generator->generateVariantUrl($asset, ['path' => 'variant.jpg', 'width' => 400, 'quality' => 20]));
        self::assertSame('/quality-90', $generator->generateVariantUrl($asset, ['path' => 'variant.jpg', 'width' => 400, 'quality' => 90]));
        self::assertCount(2, array_unique($keys));
    }

    public function testVariantCacheKeyIncludesFallbackAssetPath()
    {
        $keys = [];
        $cache = $this->createStub(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturnCallback(function (string $key) use (&$keys): CacheItemInterface {
            $keys[] = $key;
            $item = $this->createStub(CacheItemInterface::class);
            $item->method('isHit')->willReturn(false);
            $item->method('set')->willReturnSelf();
            $item->method('expiresAfter')->willReturnSelf();

            return $item;
        });
        $decoratedGenerator = $this->createStub(UrlGeneratorInterface::class);
        $decoratedGenerator->method('generateVariantUrl')->willReturn('/generated');
        $generator = new CachedUrlGenerator($decoratedGenerator, $cache);

        $generator->generateVariantUrl(new ImageAsset('default', 'first.jpg'), ['width' => 400]);
        $generator->generateVariantUrl(new ImageAsset('default', 'second.jpg'), ['width' => 400]);

        self::assertCount(2, array_unique($keys));
    }

    public function testGenerateAssetUrlFallsBackOnCacheFailure()
    {
        $decoratedGenerator = $this->createMock(UrlGeneratorInterface::class);
        $decoratedGenerator->expects($this->once())
            ->method('generateAssetUrl')
            ->willReturn('https://example.com/fallback.jpg');

        $cache = $this->createStub(CacheItemPoolInterface::class);
        $cache->method('getItem')
            ->willThrowException(new \RuntimeException('Cache unavailable'));

        $generator = new CachedUrlGenerator($decoratedGenerator, $cache, 3600);
        $asset = new ImageAsset('default', 'image.jpg');

        $url = $generator->generateAssetUrl($asset);

        self::assertSame('https://example.com/fallback.jpg', $url);
    }

    public function testGenerateVariantUrlFallsBackOnCacheFailure()
    {
        $decoratedGenerator = $this->createMock(UrlGeneratorInterface::class);
        $decoratedGenerator->expects($this->once())
            ->method('generateVariantUrl')
            ->willReturn('https://example.com/variant-fallback.jpg');

        $cache = $this->createStub(CacheItemPoolInterface::class);
        $cache->method('getItem')
            ->willThrowException(new \RuntimeException('Cache unavailable'));

        $generator = new CachedUrlGenerator($decoratedGenerator, $cache, 3600);
        $asset = new ImageAsset('default', 'image.jpg');
        $variant = ['path' => 'variant.jpg', 'width' => 400];

        $url = $generator->generateVariantUrl($asset, $variant);

        self::assertSame('https://example.com/variant-fallback.jpg', $url);
    }

    public function testGenerateAssetUrlDoesNotRetryAfterCacheWriteFailure()
    {
        $decoratedGenerator = $this->createMock(UrlGeneratorInterface::class);
        $decoratedGenerator->expects(self::once())
            ->method('generateAssetUrl')
            ->willReturn('https://example.com/generated.jpg');

        $cacheItem = $this->createStub(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cache = $this->createStub(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($cacheItem);
        $cache->method('save')->willThrowException(new \RuntimeException('Cache unavailable'));

        $generator = new CachedUrlGenerator($decoratedGenerator, $cache);

        self::assertSame('https://example.com/generated.jpg', $generator->generateAssetUrl(new ImageAsset('default', 'image.jpg')));
    }

    public function testGenerateVariantUrlDoesNotRetryAfterCacheWriteFailure()
    {
        $decoratedGenerator = $this->createMock(UrlGeneratorInterface::class);
        $decoratedGenerator->expects(self::once())
            ->method('generateVariantUrl')
            ->willReturn('https://example.com/generated.jpg');

        $cacheItem = $this->createStub(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cache = $this->createStub(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($cacheItem);
        $cache->method('save')->willThrowException(new \RuntimeException('Cache unavailable'));

        $generator = new CachedUrlGenerator($decoratedGenerator, $cache);

        self::assertSame('https://example.com/generated.jpg', $generator->generateVariantUrl(new ImageAsset('default', 'image.jpg'), ['width' => 400]));
    }

    public function testGenerateAssetUrlDoesNotRetryDecoratedFailure()
    {
        $decoratedGenerator = $this->createMock(UrlGeneratorInterface::class);
        $decoratedGenerator->expects(self::once())
            ->method('generateAssetUrl')
            ->willThrowException(new \RuntimeException('Invalid CDN configuration'));

        $cacheItem = $this->createStub(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cache = $this->createStub(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($cacheItem);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid CDN configuration');

        new CachedUrlGenerator($decoratedGenerator, $cache)->generateAssetUrl(new ImageAsset('default', 'image.jpg'));
    }
}
