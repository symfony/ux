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
use Symfony\UX\Image\Renderer\DefaultImageRenderer;
use Symfony\UX\Image\UrlGenerator\UrlGeneratorInterface;

#[CoversClass(DefaultImageRenderer::class)]
final class DensityDescriptorTest extends TestCase
{
    public function testRenderWithDensityDescriptors(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generateAssetUrl')->willReturn('https://example.com/image.jpg');
        $urlGenerator->method('generateVariantUrl')->willReturnCallback(
            static fn ($asset, $variant) => 'https://example.com/'.$variant['path']
        );

        $renderer = new DefaultImageRenderer($urlGenerator);

        $asset = new ImageAsset(
            storageName: 'default',
            path: 'image.jpg',
            variants: [
                'jpeg' => [
                    [
                        'path' => 'image-1x.jpg',
                        'density' => '1x',
                        'width' => 400,
                    ],
                    [
                        'path' => 'image-2x.jpg',
                        'density' => '2x',
                        'width' => 800,
                    ],
                ],
            ]
        );

        $rendered = $renderer->render($asset);
        $html = $rendered->toPictureHtml();

        $this->assertStringContainsString('image-1x.jpg 1x', $html);
        $this->assertStringContainsString('image-2x.jpg 2x', $html);
    }

    public function testRenderMixedWidthAndDensityDescriptors(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generateAssetUrl')->willReturn('https://example.com/image.jpg');
        $urlGenerator->method('generateVariantUrl')->willReturnCallback(
            static fn ($asset, $variant) => 'https://example.com/'.$variant['path']
        );

        $renderer = new DefaultImageRenderer($urlGenerator);

        $asset = new ImageAsset(
            storageName: 'default',
            path: 'image.jpg',
            variants: [
                'jpeg' => [
                    [
                        'path' => 'image-400w.jpg',
                        'width' => 400,
                    ],
                    [
                        'path' => 'image-2x.jpg',
                        'density' => '2x',
                        'width' => 800,
                    ],
                ],
            ]
        );

        $rendered = $renderer->render($asset);
        $html = $rendered->toPictureHtml();

        // A srcset cannot mix descriptor families. Because every candidate has
        // an intrinsic width, the set is normalized to width descriptors.
        $this->assertStringContainsString('image-400w.jpg 400w', $html);
        $this->assertStringContainsString('image-2x.jpg 800w', $html);
        $this->assertStringNotContainsString('image-2x.jpg 2x', $html);
    }

    public function testRenderWithOnlyDensityDescriptors(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generateAssetUrl')->willReturn('https://example.com/icon.png');
        $urlGenerator->method('generateVariantUrl')->willReturnCallback(
            static fn ($asset, $variant) => 'https://example.com/'.$variant['path']
        );

        $renderer = new DefaultImageRenderer($urlGenerator);

        // Typical use case for icons/UI images
        $asset = new ImageAsset(
            storageName: 'default',
            path: 'icon.png',
            variants: [
                'png' => [
                    [
                        'path' => 'icon.png',
                        'density' => '1x',
                    ],
                    [
                        'path' => 'icon@2x.png',
                        'density' => '2x',
                    ],
                    [
                        'path' => 'icon@3x.png',
                        'density' => '3x',
                    ],
                ],
            ]
        );

        $rendered = $renderer->render($asset);
        $html = $rendered->toPictureHtml();

        $this->assertStringContainsString('icon.png 1x', $html);
        $this->assertStringContainsString('icon@2x.png 2x', $html);
        $this->assertStringContainsString('icon@3x.png 3x', $html);
    }
}
