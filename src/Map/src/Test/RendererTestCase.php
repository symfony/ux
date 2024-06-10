<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Test;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Renderer\RendererInterface;

/**
 * A test case to ease testing a renderer.
 */
abstract class RendererTestCase extends TestCase
{
    /**
     * @return iterable<array{expected_render: string, renderer: RendererInterface, map: Map, attributes: array<mixed>}>
     */
    abstract public function provideTestRenderMap(): iterable;

    /**
     * @dataProvider provideTestRenderMap
     */
    public function testRenderMap(string $expectedRender, RendererInterface $renderer, Map $map, array $attributes = []): void
    {
        self::assertSame($expectedRender, $renderer->renderMap($map, $attributes));
    }
}
