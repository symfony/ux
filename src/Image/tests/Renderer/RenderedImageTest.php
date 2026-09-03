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
use Symfony\UX\Image\Renderer\ImageRenderOptions;
use Symfony\UX\Image\Renderer\RenderedImage;

#[CoversClass(RenderedImage::class)]
final class RenderedImageTest extends TestCase
{
    public function testExposesRenderedState()
    {
        $asset = new ImageAsset('default', '/image.jpg');
        $sources = [['type' => 'image/webp', 'srcset' => '/image.webp 1x']];
        $options = new ImageRenderOptions(alt: 'Image');
        $rendered = new RenderedImage($asset, $sources, '/fallback.jpg', '/fallback-2x.jpg 2x', 800, 600, $options);

        self::assertSame($asset, $rendered->getAsset());
        self::assertSame($sources, $rendered->getSources());
        self::assertSame('/fallback.jpg', $rendered->getFallbackSrc());
        self::assertSame('/fallback-2x.jpg 2x', $rendered->getFallbackSrcset());
        self::assertSame(800, $rendered->getWidth());
        self::assertSame(600, $rendered->getHeight());
        self::assertSame($options, $rendered->getOptions());
    }

    public function testToImgHtmlOutputsImgOnly()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            sources: [],
            fallbackSrc: '/image.jpg',
            fallbackSrcset: '/image-2x.jpg 2x',
            width: 800,
            height: 600,
            options: new ImageRenderOptions(
                sizes: '(max-width: 600px) 100vw, 50vw',
                alt: 'Example',
                lazy: true,
                fetchPriority: 'auto',
                class: 'img-fluid',
                decoding: 'async',
            ),
        );

        $html = $rendered->toImgHtml();

        self::assertStringContainsString('<img', $html);
        self::assertStringNotContainsString('<picture', $html);
        self::assertStringContainsString('src="/image.jpg"', $html);
        self::assertStringContainsString('srcset="/image-2x.jpg 2x"', $html);
        self::assertStringContainsString('class="img-fluid"', $html);
    }

    public function testToHtmlOutputsPictureElement()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            sources: [
                ['type' => 'image/webp', 'srcset' => '/image.webp 1x'],
            ],
            fallbackSrc: '/image.jpg',
            fallbackSrcset: '/image-2x.jpg 2x',
            width: 800,
            height: 600,
            options: new ImageRenderOptions(
                sizes: '100vw',
                alt: 'Test image',
                lazy: true,
                fetchPriority: 'auto',
                class: 'hero',
                decoding: 'async',
            ),
        );

        $html = $rendered->toHtml();

        self::assertStringStartsWith('<picture>', $html);
        self::assertStringEndsWith('</picture>', $html);
        self::assertStringContainsString('<source type="image/webp"', $html);
        self::assertStringContainsString('srcset="/image.webp 1x"', $html);
        self::assertStringContainsString('<img', $html);
        self::assertStringContainsString('src="/image.jpg"', $html);
        self::assertStringContainsString('alt="Test image"', $html);
        self::assertStringContainsString('loading="lazy"', $html);
        self::assertStringContainsString('fetchpriority="auto"', $html);
        self::assertStringContainsString('decoding="async"', $html);
        self::assertStringContainsString('width="800"', $html);
        self::assertStringContainsString('height="600"', $html);
        self::assertStringContainsString('class="hero"', $html);
        self::assertStringContainsString('srcset="/image-2x.jpg 2x"', $html);
    }

    public function testToStringReturnsSameAsToHtml()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            sources: [],
            fallbackSrc: '/image.jpg',
            fallbackSrcset: null,
            width: null,
            height: null,
            options: new ImageRenderOptions(),
        );

        self::assertSame($rendered->toHtml(), (string) $rendered);
    }

    public function testToPictureHtmlReturnsSameAsToHtml()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            sources: [],
            fallbackSrc: '/image.jpg',
            fallbackSrcset: null,
            width: null,
            height: null,
            options: new ImageRenderOptions(),
        );

        self::assertSame($rendered->toHtml(), $rendered->toPictureHtml());
    }

    public function testToHtmlWithoutSources()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            sources: [],
            fallbackSrc: '/image.jpg',
            fallbackSrcset: null,
            width: null,
            height: null,
            options: new ImageRenderOptions(class: ''),
        );

        $html = $rendered->toHtml();

        self::assertStringContainsString('<picture>', $html);
        self::assertStringNotContainsString('<source', $html);
        self::assertStringNotContainsString('width=', $html);
        self::assertStringNotContainsString('height=', $html);
        self::assertStringNotContainsString('class=', $html);
        self::assertStringNotContainsString('srcset=', $html);
        self::assertStringNotContainsString('sizes=', $html);
    }

    public function testToHtmlOmitsEmptySizesFromSourcesAndFallback()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            [['type' => 'image/webp', 'srcset' => '/image.webp 1x']],
            '/image.jpg',
            null,
            null,
            null,
            new ImageRenderOptions(sizes: ''),
        );

        self::assertStringNotContainsString('sizes=', $rendered->toHtml());
    }

    public function testToHtmlWithMediaAttribute()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            sources: [
                ['type' => 'image/webp', 'srcset' => '/image-sm.webp 640w', 'media' => '(max-width: 768px)'],
            ],
            fallbackSrc: '/image.jpg',
            fallbackSrcset: null,
            width: null,
            height: null,
            options: new ImageRenderOptions(),
        );

        $html = $rendered->toHtml();

        self::assertStringContainsString('media="(max-width: 768px)"', $html);
    }

    public function testFetchPriorityHigh()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            sources: [],
            fallbackSrc: '/image.jpg',
            fallbackSrcset: null,
            width: null,
            height: null,
            options: new ImageRenderOptions(fetchPriority: 'high'),
        );

        $html = $rendered->toHtml();

        self::assertStringContainsString('fetchpriority="high"', $html);
    }

    public function testFetchPriorityLow()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            sources: [],
            fallbackSrc: '/image.jpg',
            fallbackSrcset: null,
            width: null,
            height: null,
            options: new ImageRenderOptions(fetchPriority: 'low'),
        );

        $html = $rendered->toHtml();

        self::assertStringContainsString('fetchpriority="low"', $html);
    }

    public function testEagerLoading()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            sources: [],
            fallbackSrc: '/image.jpg',
            fallbackSrcset: null,
            width: null,
            height: null,
            options: new ImageRenderOptions(lazy: false),
        );

        $html = $rendered->toHtml();

        self::assertStringContainsString('loading="eager"', $html);
    }

    public function testToImgHtmlWithoutSrcsetSizesAndClass()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            sources: [],
            fallbackSrc: '/image.jpg',
            fallbackSrcset: null,
            width: null,
            height: null,
            options: new ImageRenderOptions(sizes: '', class: ''),
        );

        $html = $rendered->toImgHtml();

        self::assertStringNotContainsString('srcset=', $html);
        self::assertStringNotContainsString('sizes=', $html);
        self::assertStringNotContainsString('class=', $html);
        self::assertStringNotContainsString('width=', $html);
        self::assertStringNotContainsString('height=', $html);
    }

    public function testToImgHtmlWithWidthAndHeight()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            sources: [],
            fallbackSrc: '/image.jpg',
            fallbackSrcset: null,
            width: 1024,
            height: 768,
            options: new ImageRenderOptions(
                sizes: '50vw',
                lazy: false,
                fetchPriority: 'high',
            ),
        );

        $html = $rendered->toImgHtml();

        self::assertStringContainsString('width="1024"', $html);
        self::assertStringContainsString('height="768"', $html);
        self::assertStringContainsString('loading="eager"', $html);
        self::assertStringContainsString('fetchpriority="high"', $html);
        self::assertStringContainsString('sizes="50vw"', $html);
    }

    public function testToImgHtmlWithLowFetchPriority()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            sources: [],
            fallbackSrc: '/image.jpg',
            fallbackSrcset: null,
            width: null,
            height: null,
            options: new ImageRenderOptions(fetchPriority: 'low'),
        );

        $html = $rendered->toImgHtml();

        self::assertStringContainsString('fetchpriority="low"', $html);
    }

    public function testHtmlEscaping()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            sources: [
                ['type' => 'image/webp', 'srcset' => '/img.webp?w=800&h=600'],
            ],
            fallbackSrc: '/image.jpg?w=800&h=600',
            fallbackSrcset: null,
            width: null,
            height: null,
            options: new ImageRenderOptions(
                alt: 'Photo "test" & <stuff>',
            ),
        );

        $html = $rendered->toHtml();

        self::assertStringContainsString('alt="Photo &quot;test&quot; &amp; &lt;stuff&gt;"', $html);
        self::assertStringContainsString('src="/image.jpg?w=800&amp;h=600"', $html);
    }

    public function testInvalidUtf8IsSubstituted()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            [],
            '/image.jpg',
            null,
            null,
            null,
            new ImageRenderOptions(alt: "Invalid \xC3"),
        );

        self::assertStringContainsString('alt="Invalid �"', $rendered->toImgHtml());
    }

    public function testSafeCustomAttributesAreForwardedAndEscaped()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            [],
            '/image.jpg',
            null,
            10,
            10,
            new ImageRenderOptions(attributes: ['id' => 'hero', 'data-controller' => 'zoom', 'aria-hidden' => true, 'title' => '"large"']),
        );

        $html = $rendered->toImgHtml();

        self::assertStringContainsString('id="hero"', $html);
        self::assertStringContainsString('data-controller="zoom"', $html);
        self::assertStringContainsString('aria-hidden', $html);
        self::assertStringContainsString('title="&quot;large&quot;"', $html);
    }

    public function testNullAndFalseCustomAttributesAreOmitted()
    {
        $rendered = new RenderedImage(
            new ImageAsset('default', '/image.jpg'),
            [],
            '/image.jpg',
            null,
            null,
            null,
            new ImageRenderOptions(attributes: ['data-null' => null, 'data-false' => false]),
        );

        self::assertStringNotContainsString('data-null', $rendered->toImgHtml());
        self::assertStringNotContainsString('data-false', $rendered->toImgHtml());
    }

    public function testUnsafeEventAttributeIsRejected()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsafe image attribute');

        new ImageRenderOptions(attributes: ['onerror' => 'alert(1)']);
    }
}
