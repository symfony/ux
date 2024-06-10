<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Tests\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Exception\LogicException;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Renderer\RendererInterface;
use Symfony\UX\Map\Renderer\Renderers;

class RenderersTest extends TestCase
{
    public function testConstructWithoutRenderers(): void
    {
        self::expectException(LogicException::class);
        self::expectExceptionMessage('"Symfony\UX\Map\Renderer\Renderers" must have at least one renderer configured.');

        new Renderers([]);
    }

    public function testRenderMapWithDefaultRenderer(): void
    {
        $defaultRenderer = $this->createMock(RendererInterface::class);
        $defaultRenderer->expects(self::once())->method('renderMap')->willReturn('<div data-controller="@symfony/ux-map-default"></div>');

        $renderers = new Renderers(['default' => $defaultRenderer]);

        self::assertSame('<div data-controller="@symfony/ux-map-default"></div>', $renderers->renderMap(new Map()));
    }

    public function testRenderMapWithCustomRenderer(): void
    {
        $defaultRenderer = $this->createMock(RendererInterface::class);
        $defaultRenderer->expects(self::never())->method('renderMap');

        $customRenderer = $this->createMock(RendererInterface::class);
        $customRenderer->expects(self::once())->method('renderMap')->willReturn('<div data-controller="@symfony/ux-map-custom"></div>');

        $renderers = new Renderers(['default' => $defaultRenderer, 'custom' => $customRenderer]);

        $map = new Map(rendererName: 'custom');

        self::assertSame('<div data-controller="@symfony/ux-map-custom"></div>', $renderers->renderMap($map));
    }

    public function testRenderMapWithUnknownRenderer(): void
    {
        self::expectException(LogicException::class);
        self::expectExceptionMessage('The "unknown" renderer does not exist (available renderers: "default").');

        $defaultRenderer = $this->createMock(RendererInterface::class);
        $defaultRenderer->expects(self::never())->method('renderMap');

        $renderers = new Renderers(['default' => $defaultRenderer]);

        $map = new Map(rendererName: 'unknown');

        $renderers->renderMap($map);
    }
}
