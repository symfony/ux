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

use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\ImageRenderer;

final class ImageRendererTest extends TestCase
{
    private ImageRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new ImageRenderer();
    }

    // === renderImg tests ===

    public function testRenderBasicImg()
    {
        $html = $this->renderer->renderImg(src: '/photo.jpg', alt: 'A photo');

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('src="/photo.jpg"', $html);
        $this->assertStringContainsString('alt="A photo"', $html);
    }

    public function testRenderImgWithAllAttributes()
    {
        $html = $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'A photo',
            width: 800,
            height: 600,
            loading: 'lazy',
            decoding: 'async',
            fetchpriority: 'high',
            crossorigin: 'anonymous',
            referrerpolicy: 'no-referrer',
        );

        $this->assertStringContainsString('width="800"', $html);
        $this->assertStringContainsString('height="600"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('decoding="async"', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);
        $this->assertStringContainsString('crossorigin="anonymous"', $html);
        $this->assertStringContainsString('referrerpolicy="no-referrer"', $html);
    }

    public function testRenderImgWithSrcsetString()
    {
        $html = $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'A photo',
            srcset: '/photo-480.jpg 480w, /photo-800.jpg 800w',
            sizes: '(max-width: 600px) 480px, 800px',
        );

        $this->assertStringContainsString('srcset="/photo-480.jpg 480w, /photo-800.jpg 800w"', $html);
        $this->assertStringContainsString('sizes="(max-width: 600px) 480px, 800px"', $html);
    }

    public function testRenderImgWithSrcsetArray()
    {
        $html = $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'A photo',
            srcset: ['/photo-480.jpg 480w', '/photo-800.jpg 800w'],
            sizes: ['(max-width: 600px) 480px', '800px'],
        );

        $this->assertStringContainsString('srcset="/photo-480.jpg 480w, /photo-800.jpg 800w"', $html);
        $this->assertStringContainsString('sizes="(max-width: 600px) 480px, 800px"', $html);
    }

    public function testRenderImgWithIsmap()
    {
        $html = $this->renderer->renderImg(src: '/map.jpg', alt: 'Map', ismap: true);

        $this->assertStringContainsString(' ismap', $html);
        $this->assertStringNotContainsString('ismap="', $html);
    }

    public function testRenderImgWithIsmapFalse()
    {
        $html = $this->renderer->renderImg(src: '/map.jpg', alt: 'Map', ismap: false);

        $this->assertStringNotContainsString('ismap', $html);
    }

    public function testRenderImgWithUsemap()
    {
        $html = $this->renderer->renderImg(src: '/map.jpg', alt: 'Map', usemap: '#mymap');

        $this->assertStringContainsString('usemap="#mymap"', $html);
    }

    public function testRenderImgWithExtraAttributes()
    {
        $html = $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'A photo',
            attributes: ['class' => 'rounded shadow', 'id' => 'hero', 'data-lightbox' => 'gallery'],
        );

        $this->assertStringContainsString('class="rounded shadow"', $html);
        $this->assertStringContainsString('id="hero"', $html);
        $this->assertStringContainsString('data-lightbox="gallery"', $html);
    }

    public function testRenderImgWithEmptyAlt()
    {
        $html = $this->renderer->renderImg(src: '/decorative.jpg', alt: '');

        $this->assertStringContainsString('alt=""', $html);
    }

    public function testRenderImgEscapesAttributes()
    {
        $html = $this->renderer->renderImg(src: '/photo.jpg?a=1&b=2', alt: 'A "quoted" <photo>');

        $this->assertStringContainsString('src="/photo.jpg?a=1&amp;b=2"', $html);
        $this->assertStringContainsString('alt="A &quot;quoted&quot; &lt;photo&gt;"', $html);
    }

    public function testRenderImgIsSelfClosing()
    {
        $html = $this->renderer->renderImg(src: '/photo.jpg', alt: 'Photo');

        $this->assertStringEndsWith('>', $html);
        $this->assertStringNotContainsString('</img>', $html);
    }

    // === Validation tests ===

    public function testRenderImgThrowsWhenAltIsNull()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('alt');

        $this->renderer->renderImg(src: '/photo.jpg');
    }

    public function testRenderImgThrowsWhenNoSrcAndNoSrcset()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->renderer->renderImg(alt: 'A photo');
    }

    public function testRenderImgAllowsSrcsetWithoutSrc()
    {
        $html = $this->renderer->renderImg(
            alt: 'A photo',
            srcset: '/photo-480.jpg 480w, /photo-800.jpg 800w',
            sizes: '100vw',
        );

        $this->assertStringContainsString('srcset=', $html);
        $this->assertStringNotContainsString('src=', $html);
    }

    public function testRenderImgThrowsOnInvalidLoading()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('loading');

        $this->renderer->renderImg(src: '/photo.jpg', alt: 'Photo', loading: 'invalid');
    }

    public function testRenderImgThrowsOnInvalidDecoding()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('decoding');

        $this->renderer->renderImg(src: '/photo.jpg', alt: 'Photo', decoding: 'invalid');
    }

    public function testRenderImgThrowsOnInvalidFetchpriority()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('fetchpriority');

        $this->renderer->renderImg(src: '/photo.jpg', alt: 'Photo', fetchpriority: 'medium');
    }

    public function testRenderImgThrowsOnInvalidCrossorigin()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('crossorigin');

        $this->renderer->renderImg(src: '/photo.jpg', alt: 'Photo', crossorigin: 'invalid');
    }

    public function testRenderImgThrowsOnInvalidReferrerpolicy()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('referrerpolicy');

        $this->renderer->renderImg(src: '/photo.jpg', alt: 'Photo', referrerpolicy: 'invalid');
    }

    public function testRenderImgThrowsOnSizesAutoWithoutLazyLoading()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('auto');

        $this->renderer->renderImg(src: '/photo.jpg', alt: 'Photo', srcset: '/photo.jpg 800w', sizes: 'auto');
    }

    public function testRenderImgAllowsSizesAutoWithLazyLoading()
    {
        $html = $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'Photo',
            srcset: '/photo.jpg 800w',
            sizes: 'auto',
            loading: 'lazy',
        );

        $this->assertStringContainsString('sizes="auto"', $html);
    }

    public function testRenderImgThrowsOnSizesAutoWithFallbackWithoutLazyLoading()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('auto');

        $this->renderer->renderImg(src: '/photo.jpg', alt: 'Photo', srcset: '/photo.jpg 800w', sizes: 'auto, 100vw');
    }

    public function testRenderImgAllowsSizesAutoWithFallbackAndLazyLoading()
    {
        $html = $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'Photo',
            srcset: '/photo.jpg 800w',
            sizes: 'auto, 100vw',
            loading: 'lazy',
        );

        $this->assertStringContainsString('sizes="auto, 100vw"', $html);
    }

    public function testRenderImgThrowsOnMixedSrcsetDescriptors()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not mix');

        $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'Photo',
            srcset: '/photo-480.jpg 480w, /photo-2x.jpg 2x',
        );
    }

    public function testRenderImgThrowsOnDuplicateWidthDescriptors()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate');

        $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'Photo',
            srcset: '/photo-a.jpg 480w, /photo-b.jpg 480w',
        );
    }

    public function testRenderImgThrowsOnDuplicateDensityDescriptors()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate');

        $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'Photo',
            srcset: '/photo-a.jpg 2x, /photo-b.jpg 2x',
        );
    }

    public function testRenderImgAllowsValidWidthDescriptors()
    {
        $html = $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'Photo',
            srcset: '/photo-480.jpg 480w, /photo-800.jpg 800w, /photo-1200.jpg 1200w',
            sizes: '100vw',
        );

        $this->assertStringContainsString('srcset=', $html);
    }

    public function testRenderImgAllowsValidDensityDescriptors()
    {
        $html = $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'Photo',
            srcset: '/photo.jpg 1x, /photo-2x.jpg 2x',
        );

        $this->assertStringContainsString('srcset=', $html);
    }

    public function testRenderSourceThrowsOnMixedSrcsetDescriptors()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not mix');

        $this->renderer->renderSource(
            srcset: '/photo-480.webp 480w, /photo-2x.webp 2x',
        );
    }

    public function testRenderSourceThrowsOnDuplicateDescriptors()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate');

        $this->renderer->renderSource(
            srcset: '/photo-a.webp 800w, /photo-b.webp 800w',
        );
    }

    public function testRenderImgThrowsOnSizesWithDensityDescriptors()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('width descriptors');

        $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'Photo',
            srcset: '/photo.jpg 1x, /photo-2x.jpg 2x',
            sizes: '100vw',
        );
    }

    public function testRenderImgThrowsOnSizesWithNoDescriptors()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('width descriptors');

        $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'Photo',
            srcset: '/photo.jpg',
            sizes: '100vw',
        );
    }

    public function testRenderImgAllowsSizesWithWidthDescriptors()
    {
        $html = $this->renderer->renderImg(
            src: '/photo.jpg',
            alt: 'Photo',
            srcset: '/photo-480.jpg 480w, /photo-800.jpg 800w',
            sizes: '(max-width: 600px) 480px, 800px',
        );

        $this->assertStringContainsString('sizes=', $html);
    }

    // === renderSource tests ===

    public function testRenderSource()
    {
        $html = $this->renderer->renderSource(
            srcset: '/photo.avif',
            type: 'image/avif',
        );

        $this->assertStringContainsString('<source', $html);
        $this->assertStringContainsString('srcset="/photo.avif"', $html);
        $this->assertStringContainsString('type="image/avif"', $html);
        $this->assertStringNotContainsString('</source>', $html);
    }

    public function testRenderSourceWithMedia()
    {
        $html = $this->renderer->renderSource(
            srcset: '/photo-wide.webp',
            type: 'image/webp',
            media: '(min-width: 800px)',
        );

        $this->assertStringContainsString('media="(min-width: 800px)"', $html);
    }

    public function testRenderSourceWithDimensions()
    {
        $html = $this->renderer->renderSource(
            srcset: '/photo.webp',
            width: 800,
            height: 600,
        );

        $this->assertStringContainsString('width="800"', $html);
        $this->assertStringContainsString('height="600"', $html);
    }

    public function testRenderSourceWithSrcsetArray()
    {
        $html = $this->renderer->renderSource(
            srcset: ['/photo-480.webp 480w', '/photo-800.webp 800w'],
            sizes: '100vw',
        );

        $this->assertStringContainsString('srcset="/photo-480.webp 480w, /photo-800.webp 800w"', $html);
    }

    public function testRenderSourceThrowsWhenSrcInAttributes()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('src');

        $this->renderer->renderSource(
            srcset: '/photo.webp',
            attributes: ['src' => '/photo.jpg'],
        );
    }

    // === renderPicture tests ===

    public function testRenderPicture()
    {
        $html = $this->renderer->renderPicture(
            sources: [
                ['srcset' => '/photo.avif', 'type' => 'image/avif'],
                ['srcset' => '/photo.webp', 'type' => 'image/webp'],
            ],
            src: '/photo.jpg',
            alt: 'A photo',
            width: 800,
            height: 600,
        );

        $this->assertStringStartsWith('<picture>', $html);
        $this->assertStringEndsWith('</picture>', $html);
        $this->assertStringContainsString('<source srcset="/photo.avif" type="image/avif" />', $html);
        $this->assertStringContainsString('<source srcset="/photo.webp" type="image/webp" />', $html);
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('src="/photo.jpg"', $html);
        $this->assertStringContainsString('alt="A photo"', $html);
    }

    public function testRenderPictureWithAttributes()
    {
        $html = $this->renderer->renderPicture(
            sources: [['srcset' => '/photo.webp', 'type' => 'image/webp']],
            src: '/photo.jpg',
            alt: 'A photo',
            attributes: ['class' => 'hero-picture'],
        );

        $this->assertStringContainsString('<picture class="hero-picture">', $html);
    }

    public function testRenderPictureWithImgAttributes()
    {
        $html = $this->renderer->renderPicture(
            sources: [['srcset' => '/photo.webp', 'type' => 'image/webp']],
            src: '/photo.jpg',
            alt: 'A photo',
            imgAttributes: ['class' => 'img-fluid'],
        );

        $this->assertStringContainsString('class="img-fluid"', $html);
    }
}
