<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Renderer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Profile\ProfileRegistry;
use Symfony\UX\Image\Renderer\DefaultImageRenderer;
use Symfony\UX\Image\Renderer\ImageRenderOptions;
use Symfony\UX\Image\Renderer\RenderedImage;
use Symfony\UX\Image\UrlGenerator\UrlGeneratorInterface;

#[CoversClass(DefaultImageRenderer::class)]
final class DefaultImageRendererTest extends TestCase
{
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->urlGenerator = new class implements UrlGeneratorInterface {
            public function generateAssetUrl(ImageAsset $asset): string
            {
                return '/generated'.$asset->path;
            }

            public function generateVariantUrl(ImageAsset $asset, array $variant): string
            {
                return '/generated'.($variant['path'] ?? $asset->path);
            }
        };
    }

    public function testRenderWithImageAsset()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('cdn', '/cdn/photo.jpg');
        $result = $renderer->render($asset);

        self::assertInstanceOf(RenderedImage::class, $result);
        self::assertSame('/generated/cdn/photo.jpg', $result->fallbackSrc);
        self::assertSame('cdn', $result->asset->storageName);
    }

    public function testRenderWithVariants()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'webp' => [
                ['path' => '/default/photo_mobile.webp', 'width' => 640],
                ['path' => '/default/photo_desktop.webp', 'width' => 1920],
            ],
            'jpeg' => [
                ['path' => '/default/photo_mobile.jpg', 'width' => 640],
            ],
        ]);

        $result = $renderer->render($asset);

        self::assertNotEmpty($result->sources);

        // Verify webp source is present
        $webpSource = null;
        foreach ($result->sources as $source) {
            if ('image/webp' === $source['type']) {
                $webpSource = $source;
                break;
            }
        }

        self::assertNotNull($webpSource);
        self::assertStringContainsString('photo_mobile.webp', $webpSource['srcset']);
        self::assertStringContainsString('photo_desktop.webp', $webpSource['srcset']);
    }

    public function testSourceUsesPersistedMimeTypeAndCanonicalJpegFallback()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);
        $explicit = $renderer->render(new ImageAsset('default', '/photo.jpg', variants: [
            'custom' => [['path' => '/photo.custom', 'width' => 100, 'mimeType' => 'image/x-custom']],
        ]));
        $jpeg = $renderer->render(new ImageAsset('default', '/photo.jpg', variants: [
            'jpg' => [['path' => '/photo.jpg', 'width' => 100]],
        ]));

        self::assertSame('image/x-custom', $explicit->sources[0]['type']);
        self::assertSame('image/jpeg', $jpeg->sources[0]['type']);
    }

    public function testRenderWithNoVariantsReturnsEmptySources()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg');
        $result = $renderer->render($asset);

        self::assertSame([], $result->sources);
    }

    public function testDimensionsFromAsset()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', width: 1024, height: 768);
        $result = $renderer->render($asset);

        self::assertSame(1024, $result->width);
        self::assertSame(768, $result->height);
    }

    public function testDimensionsFromVariant()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'jpeg' => [
                ['path' => '/default/photo_full.jpg', 'width' => 1920, 'height' => 1080],
            ],
        ]);

        $result = $renderer->render($asset);

        self::assertSame(1920, $result->width);
        self::assertSame(1080, $result->height);
    }

    public function testFallbackSrc()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg');
        $result = $renderer->render($asset);

        self::assertSame('/generated/default/photo.jpg', $result->fallbackSrc);
    }

    public function testFallbackSrcUsesDefaultFormatVariant()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'jpeg' => [
                ['path' => '/default/photo_main.jpg', 'width' => 800],
            ],
        ]);

        $result = $renderer->render($asset);

        self::assertSame('/generated/default/photo_main.jpg', $result->fallbackSrc);
    }

    public function testRenderOptionsArePassedThrough()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg');
        $options = new ImageRenderOptions(
            alt: 'A beautiful photo',
            lazy: false,
            fetchPriority: 'high',
            class: 'hero-image',
            decoding: 'sync',
        );

        $result = $renderer->render($asset, $options);

        self::assertSame('A beautiful photo', $result->options->alt);
        self::assertFalse($result->options->lazy);
        self::assertSame('high', $result->options->fetchPriority);
        self::assertSame('hero-image', $result->options->class);
        self::assertSame('sync', $result->options->decoding);
    }

    public function testDefaultSizesOption()
    {
        $renderer = new DefaultImageRenderer(
            $this->urlGenerator,
            defaultSizes: '(max-width: 768px) 100vw, 50vw',
        );

        $asset = new ImageAsset('default', '/default/photo.jpg');
        $result = $renderer->render($asset);

        self::assertSame('(max-width: 768px) 100vw, 50vw', $result->options->sizes);
    }

    public function testSizesFromOptionsOverridesDefault()
    {
        $renderer = new DefaultImageRenderer(
            $this->urlGenerator,
            defaultSizes: '100vw',
        );

        $asset = new ImageAsset('default', '/default/photo.jpg');
        $options = new ImageRenderOptions(sizes: '50vw');
        $result = $renderer->render($asset, $options);

        self::assertSame('50vw', $result->options->sizes);
    }

    public function testProfileRenderingDefaultsAreUsedAndOptionsStillWin()
    {
        $profiles = new ProfileRegistry([
            'hero' => [
                'sizes' => '(max-width: 800px) 100vw, 800px',
                'preferred_formats' => ['webp', 'avif'],
                'formats' => ['avif', 'webp'],
                'variants' => ['wide' => ['width' => 800]],
            ],
        ]);
        $renderer = new DefaultImageRenderer($this->urlGenerator, profiles: $profiles);
        $asset = new ImageAsset('default', '/photo.jpg', variants: [
            'avif' => [['path' => '/photo.avif', 'width' => 800]],
            'webp' => [['path' => '/photo.webp', 'width' => 800]],
        ], profile: 'hero');

        $fromProfile = $renderer->render($asset);
        self::assertSame('(max-width: 800px) 100vw, 800px', $fromProfile->options->sizes);
        self::assertSame('image/webp', $fromProfile->sources[0]['type']);

        $explicit = $renderer->render($asset, new ImageRenderOptions(sizes: '50vw'));
        self::assertSame('50vw', $explicit->options->sizes);
    }

    public function testMediaOnCanonicalFormatVariantsCreatesArtDirectionSources()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);
        $asset = new ImageAsset('default', '/photo.jpg', variants: [
            'webp' => [
                ['name' => 'mobile', 'path' => '/photo_mobile.webp', 'width' => 640, 'media' => '(max-width: 640px)'],
                ['name' => 'desktop', 'path' => '/photo_desktop.webp', 'width' => 1280, 'media' => '(min-width: 641px)'],
            ],
        ]);

        $rendered = $renderer->render($asset);

        self::assertCount(2, $rendered->sources);
        self::assertSame('(max-width: 640px)', $rendered->sources[0]['media']);
        self::assertStringContainsString('photo_mobile.webp', $rendered->sources[0]['srcset']);
        self::assertSame('(min-width: 641px)', $rendered->sources[1]['media']);
    }

    public function testVariantFiltering()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'webp' => [
                ['name' => 'mobile', 'path' => '/default/photo_mobile.webp', 'width' => 640],
                ['name' => 'desktop', 'path' => '/default/photo_desktop.webp', 'width' => 1920],
            ],
        ]);

        $options = new ImageRenderOptions(variant: 'mobile');
        $result = $renderer->render($asset, $options);

        foreach ($result->sources as $source) {
            self::assertStringContainsString('mobile', $source['srcset']);
            self::assertStringNotContainsString('desktop', $source['srcset']);
        }
    }

    public function testVariantFilteringUsesVariantDimensions()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);
        $asset = new ImageAsset('default', '/photo.jpg', width: 1600, height: 900, variants: [
            'jpeg' => [
                ['name' => 'thumbnail', 'path' => '/photo_thumbnail.jpg', 'width' => 300, 'height' => 300],
            ],
        ]);

        $result = $renderer->render($asset, new ImageRenderOptions(variant: 'thumbnail'));

        self::assertSame(300, $result->width);
        self::assertSame(300, $result->height);
    }

    public function testUnknownVariantFailsExplicitly()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);
        $asset = new ImageAsset('default', '/photo.jpg', variants: [
            'jpeg' => [['name' => 'thumbnail', 'path' => '/photo_thumbnail.jpg']],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The image variant "missing" does not exist.');

        $renderer->render($asset, new ImageRenderOptions(variant: 'missing'));
    }

    public function testVariantOnAssetWithoutVariantsFailsExplicitly()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The image variant "thumbnail" does not exist.');

        $renderer->render(new ImageAsset('default', '/photo.jpg'), new ImageRenderOptions(variant: 'thumbnail'));
    }

    public function testExplicitSrcsetOverridesGeneratedFallback()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);
        $asset = new ImageAsset('default', '/photo.jpg', variants: [
            'jpeg' => [['path' => '/generated.jpg', 'width' => 800]],
        ]);

        $result = $renderer->render($asset, new ImageRenderOptions(srcset: ['/small.jpg 400w', '/large.jpg 800w']));

        self::assertSame('/small.jpg 400w, /large.jpg 800w', $result->fallbackSrcset);
    }

    public function testRenderWithMultiRatioVariants()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'webp' => [
                ['path' => '/default/photo_mobile.webp', 'width' => 640, 'media' => '(max-width: 768px)'],
                ['path' => '/default/photo_desktop.webp', 'width' => 1920, 'media' => '(min-width: 769px)'],
            ],
            'jpeg' => [
                ['path' => '/default/photo_mobile.jpg', 'width' => 640, 'media' => '(max-width: 768px)'],
            ],
        ]);

        $result = $renderer->render($asset);

        self::assertNotEmpty($result->sources);

        // Check that media queries are present in sources
        $mediaValues = array_filter(array_column($result->sources, 'media'));
        self::assertNotEmpty($mediaValues);

        // Check that webp mobile source has media attribute
        $mobileWebp = null;
        foreach ($result->sources as $source) {
            if ('image/webp' === $source['type'] && isset($source['media']) && str_contains($source['media'], '768')) {
                $mobileWebp = $source;
                break;
            }
        }
        self::assertNotNull($mobileWebp);
        self::assertStringContainsString('photo_mobile.webp', $mobileWebp['srcset']);
    }

    public function testArtDirectionGroupsOnlyContainActualVariants()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'webp' => [
                ['path' => '/default/photo_mobile.webp', 'width' => 640, 'media' => '(max-width: 768px)'],
            ],
        ]);

        $result = $renderer->render($asset);

        self::assertCount(1, $result->sources);
        self::assertSame('image/webp', $result->sources[0]['type']);
        self::assertStringContainsString('photo_mobile.webp', $result->sources[0]['srcset']);
    }

    public function testDensityDescriptorInSrcset()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'webp' => [
                ['path' => '/default/photo_1x.webp', 'density' => '1x'],
                ['path' => '/default/photo_2x.webp', 'density' => '2x'],
            ],
        ]);

        $result = $renderer->render($asset);

        $webpSource = null;
        foreach ($result->sources as $source) {
            if ('image/webp' === $source['type']) {
                $webpSource = $source;
                break;
            }
        }

        self::assertNotNull($webpSource);
        self::assertStringContainsString('1x', $webpSource['srcset']);
        self::assertStringContainsString('2x', $webpSource['srcset']);
        // Density descriptors used instead of width descriptors
        self::assertStringNotContainsString('640w', $webpSource['srcset']);
    }

    public function testSrcsetEntryWithNoWidthNoDensity()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'webp' => [
                ['path' => '/default/photo.webp'],
            ],
        ]);

        $result = $renderer->render($asset);

        $webpSource = null;
        foreach ($result->sources as $source) {
            if ('image/webp' === $source['type']) {
                $webpSource = $source;
                break;
            }
        }

        self::assertNotNull($webpSource);
        // Just URL, no descriptor
        self::assertSame('/generated/default/photo.webp', $webpSource['srcset']);
    }

    public function testFallbackSrcWithNoVariantsUsesAssetUrl()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: []);
        $result = $renderer->render($asset);

        self::assertSame('/generated/default/photo.jpg', $result->fallbackSrc);
    }

    public function testFallbackSrcsetWithVariants()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'jpeg' => [
                ['path' => '/default/photo_sm.jpg', 'width' => 640],
                ['path' => '/default/photo_lg.jpg', 'width' => 1920],
            ],
        ]);

        $result = $renderer->render($asset);

        self::assertNotNull($result->fallbackSrcset);
        self::assertStringContainsString('640w', $result->fallbackSrcset);
        self::assertStringContainsString('1920w', $result->fallbackSrcset);
    }

    public function testFallbackSrcsetIsNullWhenNoVariants()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: []);
        $result = $renderer->render($asset);

        self::assertNull($result->fallbackSrcset);
    }

    public function testFallbackSrcsetUsesCanonicalFormatKey()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'jpeg' => [['path' => '/default/photo.jpeg', 'width' => 800]],
        ]);

        $result = $renderer->render($asset);

        self::assertSame('/generated/default/photo.jpeg 800w', $result->fallbackSrcset);
    }

    public function testFallbackSrcWithPrimaryVariantPath()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'webp' => [
                ['path' => '/default/photo_primary.webp', 'width' => 800],
                ['path' => '/default/photo_large.webp', 'width' => 1920],
            ],
        ]);

        $result = $renderer->render($asset);

        // WebP remains a <source>; the universal <img> fallback uses the original.
        self::assertSame('/generated/default/photo.jpg', $result->fallbackSrc);
    }

    public function testDimensionsResolvedFromVariantWhenAssetHasNone()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', width: null, height: null, variants: [
            'webp' => [
                ['path' => '/default/photo.webp', 'width' => 1024, 'height' => 768],
            ],
        ]);

        $result = $renderer->render($asset);

        self::assertSame(1024, $result->width);
        self::assertSame(768, $result->height);
    }

    public function testSourcesWithVariantsMissingPathFailExplicitly()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('path');

        new ImageAsset('default', '/default/photo.jpg', variants: [
            'webp' => [
                ['width' => 640],
                ['path' => '/default/photo.webp', 'width' => 1920],
            ],
        ]);
    }

    public function testPreferredFormatsOrder()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'png' => [['path' => '/default/photo.png', 'width' => 800]],
            'avif' => [['path' => '/default/photo.avif', 'width' => 800]],
            'webp' => [['path' => '/default/photo.webp', 'width' => 800]],
            'jpeg' => [['path' => '/default/photo.jpg', 'width' => 800]],
        ]);

        $result = $renderer->render($asset);

        // Sources should be ordered: avif, webp, jpeg, png
        self::assertCount(4, $result->sources);
        self::assertSame('image/avif', $result->sources[0]['type']);
        self::assertSame('image/webp', $result->sources[1]['type']);
        self::assertSame('image/jpeg', $result->sources[2]['type']);
        self::assertSame('image/png', $result->sources[3]['type']);
    }

    public function testMultiRatioWithDensityDescriptors()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'webp' => [
                ['path' => '/default/photo_mobile_1x.webp', 'density' => '1x', 'media' => '(max-width: 768px)'],
                ['path' => '/default/photo_mobile_2x.webp', 'density' => '2x', 'media' => '(max-width: 768px)'],
            ],
        ]);

        $result = $renderer->render($asset);

        self::assertNotEmpty($result->sources);
        $source = $result->sources[0];
        self::assertSame('image/webp', $source['type']);
        self::assertStringContainsString('1x', $source['srcset']);
        self::assertStringContainsString('2x', $source['srcset']);
        self::assertSame('(max-width: 768px)', $source['media']);
    }

    public function testMultiRatioWithoutMediaAttribute()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);

        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'webp' => [
                ['path' => '/default/photo.webp', 'width' => 800],
            ],
        ]);

        $result = $renderer->render($asset);

        self::assertNotEmpty($result->sources);
        self::assertArrayNotHasKey('media', $result->sources[0]);
    }

    public function testArtDirectionSourcesPrecedeUnconditionalFallbackSource()
    {
        $renderer = new DefaultImageRenderer($this->urlGenerator);
        $asset = new ImageAsset('default', '/default/photo.jpg', variants: [
            'webp' => [
                ['path' => '/default/photo_fallback.webp', 'width' => 1280],
                ['path' => '/default/photo_mobile.webp', 'width' => 640, 'media' => '(max-width: 768px)'],
            ],
        ]);

        $result = $renderer->render($asset);

        self::assertCount(2, $result->sources);
        self::assertSame('(max-width: 768px)', $result->sources[0]['media'] ?? null);
        self::assertArrayNotHasKey('media', $result->sources[1]);
    }
}
