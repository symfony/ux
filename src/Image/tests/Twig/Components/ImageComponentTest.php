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
use Symfony\UX\TwigComponent\ComponentAttributes;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Runtime\EscaperRuntime;

#[CoversClass(Image::class)]
final class ImageComponentTest extends TestCase
{
    public function testRenderedReturnsNullWhenSrcIsNull()
    {
        $renderer = $this->createMock(ImageRendererInterface::class);
        $renderer->expects(self::never())->method('render');

        $component = new Image(new ImageRuntime($renderer));

        self::assertNull($component->rendered());
    }

    public function testRenderedUsesRuntimeAndCachesResult()
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

    public function testRenderedPassesAllRenderingOptions()
    {
        $asset = new ImageAsset('default', '/default/photo.jpg');
        $rendered = new RenderedImage($asset, [], '/default/photo.jpg', null, null, null, new ImageRenderOptions());
        $renderer = $this->createMock(ImageRendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with($asset, self::callback(static fn (ImageRenderOptions $options): bool => 'sync' === $options->decoding
                && ['/small.jpg 400w', '/large.jpg 800w'] === $options->srcset
                && [
                    'aria-expanded' => 'false',
                    'data-options' => '{"zoom":true}',
                    'hidden' => '',
                    'title' => null,
                ] === $options->attributes))
            ->willReturn($rendered);

        $component = new Image(new ImageRuntime($renderer));
        $component->src = $asset;
        $component->decoding = 'sync';
        $component->srcset = ['/small.jpg 400w', '/large.jpg 800w'];

        self::assertSame($rendered, $component->rendered([
            'aria-expanded' => false,
            'data-options' => ['zoom' => true],
            'hidden' => true,
            'title' => null,
        ]));
    }

    public function testTemplateForwardsExtraAttributes()
    {
        $asset = new ImageAsset('default', '/default/photo.jpg');
        $renderer = $this->createStub(ImageRendererInterface::class);
        $renderer->method('render')->willReturnCallback(static fn (ImageAsset $asset, ImageRenderOptions $options): RenderedImage => new RenderedImage(
            $asset,
            [],
            '/default/photo.jpg',
            null,
            null,
            null,
            $options,
        ));

        $component = new Image(new ImageRuntime($renderer));
        $component->src = $asset;

        $twig = new Environment(new FilesystemLoader(__DIR__.'/../../../templates/components'));
        $html = $twig->render('Image.html.twig', [
            'this' => $component,
            'attributes' => new ComponentAttributes([
                'id' => 'hero',
                'aria-expanded' => false,
                'data-options' => ['zoom' => true],
                'hidden' => true,
            ], $twig->getRuntime(EscaperRuntime::class)),
        ]);

        self::assertStringContainsString('id="hero"', $html);
        self::assertStringContainsString('aria-expanded="false"', $html);
        self::assertStringContainsString('data-options="{&quot;zoom&quot;:true}"', $html);
        self::assertStringContainsString('hidden=""', $html);
    }
}
