<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Twig;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Profile\ProfileRegistry;
use Symfony\UX\Image\Renderer\DefaultImageRenderer;
use Symfony\UX\Image\Renderer\ImageRendererInterface;
use Symfony\UX\Image\Renderer\ImageRenderOptions;
use Symfony\UX\Image\Renderer\RenderedImage;
use Symfony\UX\Image\Twig\ImageExtension;
use Symfony\UX\Image\Twig\ImageRuntime;
use Symfony\UX\Image\UrlGenerator\UrlGeneratorInterface;

#[CoversClass(ImageExtension::class)]
#[CoversClass(ImageRuntime::class)]
final class ImageExtensionTest extends TestCase
{
    public function testRegistersUxImageAndUxPictureFunctions()
    {
        $renderer = $this->createStub(ImageRendererInterface::class);
        $runtime = new ImageRuntime($renderer);

        $extension = new ImageExtension($runtime);

        $functionNames = array_map(static fn ($f) => $f->getName(), $extension->getFunctions());

        self::assertContains('ux_image', $functionNames);
        self::assertContains('ux_picture', $functionNames);
    }

    public function testDoesNotImplementGlobalsInterface()
    {
        $renderer = $this->createStub(ImageRendererInterface::class);
        $runtime = new ImageRuntime($renderer);

        $extension = new ImageExtension($runtime);

        self::assertNotInstanceOf(\Twig\Extension\GlobalsInterface::class, $extension);
    }

    public function testUxImageReturnsImgMarkup()
    {
        $asset = new ImageAsset('default', '/foo.jpg');
        $rendered = new RenderedImage(
            asset: $asset,
            sources: [],
            fallbackSrc: '/foo.jpg',
            fallbackSrcset: null,
            width: null,
            height: null,

            options: new ImageRenderOptions(),
        );

        $renderer = $this->createMock(ImageRendererInterface::class);
        $renderer->expects(self::once())->method('render')->willReturn($rendered);
        $runtime = new ImageRuntime($renderer);
        $extension = new ImageExtension($runtime);

        $html = $extension->renderImage($asset);

        self::assertStringStartsWith('<img', $html);
        self::assertStringNotContainsString('<picture', $html);
    }

    public function testUxPictureReturnsPictureMarkup()
    {
        $asset = new ImageAsset('default', '/foo.jpg');
        $rendered = new RenderedImage(
            asset: $asset,
            sources: [
                ['type' => 'image/webp', 'srcset' => '/foo.webp 100w'],
            ],
            fallbackSrc: '/foo.jpg',
            fallbackSrcset: '/foo.jpg 100w',
            width: 100,
            height: 100,

            options: new ImageRenderOptions(),
        );

        $renderer = $this->createMock(ImageRendererInterface::class);
        $renderer->expects(self::once())->method('render')->willReturn($rendered);
        $runtime = new ImageRuntime($renderer);
        $extension = new ImageExtension($runtime);

        $html = $extension->renderPicture($asset);

        self::assertStringStartsWith('<picture>', $html);
        self::assertStringContainsString('<source', $html);
        self::assertStringContainsString('<img', $html);
    }

    public function testTwigFunctionUsesProfileSizesWhenOptionIsAbsent()
    {
        $urlGenerator = new class implements UrlGeneratorInterface {
            public function generateAssetUrl(ImageAsset $asset): string
            {
                return $asset->path;
            }

            public function generateVariantUrl(ImageAsset $asset, array $variant): string
            {
                return (string) ($variant['path'] ?? $asset->path);
            }
        };
        $renderer = new DefaultImageRenderer(
            $urlGenerator,
            profiles: new ProfileRegistry([
                'hero' => [
                    'sizes' => '(max-width: 800px) 100vw, 800px',
                    'formats' => ['jpeg'],
                    'variants' => ['wide' => ['width' => 800]],
                ],
            ]),
        );
        $extension = new ImageExtension(new ImageRuntime($renderer));
        $asset = new ImageAsset('default', '/hero.jpg', profile: 'hero');

        $html = $extension->renderPicture($asset);

        self::assertStringContainsString('sizes="(max-width: 800px) 100vw, 800px"', $html);
        self::assertStringNotContainsString('sizes=""', $html);
    }

    public function testRuntimeRenderConfiguredPassesOptions()
    {
        $asset = new ImageAsset('default', '/img/photo.jpg');

        $renderer = $this->createMock(ImageRendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                $asset,
                self::callback(static function (ImageRenderOptions $options): bool {
                    return '50vw' === $options->sizes
                        && 'Photo' === $options->alt
                        && false === $options->lazy
                        && 'high' === $options->fetchPriority
                        && 'hero' === $options->class
                        && 'sync' === $options->decoding
                        && ['/small.jpg 400w'] === $options->srcset
                        && ['data-controller' => 'gallery'] === $options->attributes;
                })
            )
            ->willReturn(new RenderedImage(
                asset: $asset,
                sources: [],
                fallbackSrc: '/img/photo.jpg',
                fallbackSrcset: null,
                width: null,
                height: null,
                options: new ImageRenderOptions(),
            ));

        $runtime = new ImageRuntime($renderer);
        $result = $runtime->renderConfigured($asset, [
            'sizes' => '50vw',
            'alt' => 'Photo',
            'lazy' => false,
            'fetchpriority' => 'high',
            'class' => 'hero',
            'decoding' => 'sync',
            'srcset' => ['/small.jpg 400w'],
            'attributes' => ['data-controller' => 'gallery'],
        ]);

        self::assertInstanceOf(RenderedImage::class, $result);
    }

    public function testRuntimeRenderConfiguredHonorsFetchpriorityOption()
    {
        $asset = new ImageAsset('default', '/foo.jpg');
        $rendered = new RenderedImage(
            asset: $asset,
            sources: [],
            fallbackSrc: '/foo.jpg',
            fallbackSrcset: null,
            width: null,
            height: null,
            options: new ImageRenderOptions(fetchPriority: 'high'),
        );

        $renderer = $this->createMock(ImageRendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                $asset,
                self::callback(static fn (ImageRenderOptions $options): bool => 'high' === $options->fetchPriority)
            )
            ->willReturn($rendered);

        $extension = new ImageExtension(new ImageRuntime($renderer));
        $html = $extension->renderImage($asset, ['fetchpriority' => 'high']);

        self::assertStringContainsString('fetchpriority="high"', $html);
    }

    public function testRuntimeRenderConfiguredIgnoresLegacyFetchPriorityKeys()
    {
        $asset = new ImageAsset('default', '/img/photo.jpg');

        $renderer = $this->createMock(ImageRendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                $asset,
                self::callback(static fn (ImageRenderOptions $options): bool => 'auto' === $options->fetchPriority)
            )
            ->willReturn(new RenderedImage(
                asset: $asset,
                sources: [],
                fallbackSrc: '/img/photo.jpg',
                fallbackSrcset: null,
                width: null,
                height: null,
                options: new ImageRenderOptions(),
            ));

        $runtime = new ImageRuntime($renderer);
        $runtime->renderConfigured($asset, ['fetchPriority' => 'high', 'priority' => 'high']);
    }

    public function testRuntimeRenderConfiguredWithDefaults()
    {
        $asset = new ImageAsset('default', '/img/photo.jpg');

        $renderer = $this->createMock(ImageRendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                $asset,
                self::callback(static function (ImageRenderOptions $options): bool {
                    return null === $options->sizes
                        && '' === $options->alt
                        && true === $options->lazy
                        && 'auto' === $options->fetchPriority
                        && '' === $options->class
                        && 'async' === $options->decoding;
                })
            )
            ->willReturn(new RenderedImage(
                asset: $asset,
                sources: [],
                fallbackSrc: '/img/photo.jpg',
                fallbackSrcset: null,
                width: null,
                height: null,
                options: new ImageRenderOptions(),
            ));

        $runtime = new ImageRuntime($renderer);
        $runtime->renderConfigured($asset);
    }

    public function testRuntimeRenderConfiguredFiltersByVariant()
    {
        $urlGenerator = new class implements UrlGeneratorInterface {
            public function generateAssetUrl(ImageAsset $asset): string
            {
                return '/generated'.$asset->path;
            }

            public function generateVariantUrl(ImageAsset $asset, array $variant): string
            {
                return '/generated'.($variant['path'] ?? $asset->path);
            }
        };

        $renderer = new DefaultImageRenderer($urlGenerator);
        $runtime = new ImageRuntime($renderer);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'webp' => [
                ['name' => 'mobile', 'path' => '/default/photo_mobile.webp', 'width' => 640],
                ['name' => 'desktop', 'path' => '/default/photo_desktop.webp', 'width' => 1920],
            ],
        ]);

        $result = $runtime->renderConfigured($asset, ['variant' => 'mobile']);

        self::assertNotSame([], $result->sources);
        foreach ($result->sources as $source) {
            self::assertStringContainsString('mobile', $source['srcset']);
            self::assertStringNotContainsString('desktop', $source['srcset']);
        }
    }

    public function testRuntimeGetSourcesReturnsSources()
    {
        $asset = new ImageAsset('default', '/img/photo.jpg');
        $expectedSources = [
            ['type' => 'image/webp', 'srcset' => '/img/photo.webp 800w'],
        ];

        $renderer = $this->createStub(ImageRendererInterface::class);
        $renderer->method('render')
            ->willReturn(new RenderedImage(
                asset: $asset,
                sources: $expectedSources,
                fallbackSrc: '/img/photo.jpg',
                fallbackSrcset: null,
                width: null,
                height: null,
                options: new ImageRenderOptions(),
            ));

        $runtime = new ImageRuntime($renderer);
        $sources = $runtime->getSources($asset);

        self::assertSame($expectedSources, $sources);
    }

    public function testRuntimeGetFallbackSrc()
    {
        $asset = new ImageAsset('default', '/img/photo.jpg');

        $renderer = $this->createStub(ImageRendererInterface::class);
        $renderer->method('render')
            ->willReturn(new RenderedImage(
                asset: $asset,
                sources: [],
                fallbackSrc: '/generated/img/photo.jpg',
                fallbackSrcset: null,
                width: null,
                height: null,
                options: new ImageRenderOptions(),
            ));

        $runtime = new ImageRuntime($renderer);

        self::assertSame('/generated/img/photo.jpg', $runtime->getFallbackSrc($asset));
    }

    public function testRuntimeGetFallbackSrcset()
    {
        $asset = new ImageAsset('default', '/img/photo.jpg');

        $renderer = $this->createStub(ImageRendererInterface::class);
        $renderer->method('render')
            ->willReturn(new RenderedImage(
                asset: $asset,
                sources: [],
                fallbackSrc: '/img/photo.jpg',
                fallbackSrcset: '/img/photo_sm.jpg 640w, /img/photo_lg.jpg 1920w',
                width: null,
                height: null,
                options: new ImageRenderOptions(),
            ));

        $runtime = new ImageRuntime($renderer);

        self::assertSame('/img/photo_sm.jpg 640w, /img/photo_lg.jpg 1920w', $runtime->getFallbackSrcset($asset));
    }

    public function testRuntimeGetFallbackSrcsetReturnsNull()
    {
        $asset = new ImageAsset('default', '/img/photo.jpg');

        $renderer = $this->createStub(ImageRendererInterface::class);
        $renderer->method('render')
            ->willReturn(new RenderedImage(
                asset: $asset,
                sources: [],
                fallbackSrc: '/img/photo.jpg',
                fallbackSrcset: null,
                width: null,
                height: null,
                options: new ImageRenderOptions(),
            ));

        $runtime = new ImageRuntime($renderer);

        self::assertNull($runtime->getFallbackSrcset($asset));
    }

    public function testRuntimeGetWidth()
    {
        $asset = new ImageAsset('default', '/img/photo.jpg');

        $renderer = $this->createStub(ImageRendererInterface::class);
        $renderer->method('render')
            ->willReturn(new RenderedImage(
                asset: $asset,
                sources: [],
                fallbackSrc: '/img/photo.jpg',
                fallbackSrcset: null,
                width: 1024,
                height: 768,
                options: new ImageRenderOptions(),
            ));

        $runtime = new ImageRuntime($renderer);

        self::assertSame(1024, $runtime->getWidth($asset));
    }

    public function testRuntimeGetHeight()
    {
        $asset = new ImageAsset('default', '/img/photo.jpg');

        $renderer = $this->createStub(ImageRendererInterface::class);
        $renderer->method('render')
            ->willReturn(new RenderedImage(
                asset: $asset,
                sources: [],
                fallbackSrc: '/img/photo.jpg',
                fallbackSrcset: null,
                width: 1024,
                height: 768,
                options: new ImageRenderOptions(),
            ));

        $runtime = new ImageRuntime($renderer);

        self::assertSame(768, $runtime->getHeight($asset));
    }
}
