<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Twig\Components;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Renderer\ImageRendererInterface;
use Symfony\UX\Image\Renderer\ImageRenderOptions;
use Symfony\UX\Image\Renderer\RenderedImage;
use Symfony\UX\Image\Twig\Components\Image;
use Symfony\UX\Image\Twig\ImageRuntime;

#[CoversClass(Image::class)]
final class ImageComponentTest extends TestCase
{
    public function testRenderedReturnsNullWhenSrcIsNull(): void
    {
        $renderer = $this->createMock(ImageRendererInterface::class);
        $renderer->expects(self::never())->method('render');

        $component = new Image(new ImageRuntime($renderer));

        self::assertNull($component->rendered());
    }

    public function testRenderedUsesRuntimeAndCachesResult(): void
    {
        $asset = new ImageAsset('default', '/default/photo.jpg');
        $rendered = new RenderedImage(
            asset: $asset,
            sources: [],
            fallbackSrc: '/default/photo.jpg',
            fallbackSrcset: null,
            width: 1024,
            height: 768,

            options: new ImageRenderOptions(),
        );

        $renderer = $this->createMock(ImageRendererInterface::class);
        $renderer->expects(self::once())->method('render')->willReturn($rendered);

        $component = new Image(new ImageRuntime($renderer));
        $component->src = $asset;

        self::assertSame($rendered, $component->rendered());
        self::assertSame($rendered, $component->rendered());
    }

    public function testRenderedPassesAllRenderingOptions(): void
    {
        $asset = new ImageAsset('default', '/default/photo.jpg');
        $rendered = new RenderedImage($asset, [], '/default/photo.jpg', null, null, null, new ImageRenderOptions());
        $renderer = $this->createMock(ImageRendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with($asset, self::callback(static fn (ImageRenderOptions $options): bool => 'sync' === $options->decoding
                && ['/small.jpg 400w', '/large.jpg 800w'] === $options->srcset))
            ->willReturn($rendered);

        $component = new Image(new ImageRuntime($renderer));
        $component->src = $asset;
        $component->decoding = 'sync';
        $component->srcset = ['/small.jpg 400w', '/large.jpg 800w'];

        self::assertSame($rendered, $component->rendered());
    }
}
