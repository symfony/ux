<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Ipx\Tests;

use Symfony\UX\Image\Bridge\Ipx\IpxProvider;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Renderer\RenderOptions;
use Symfony\UX\Image\Test\RendererSnapshotTestCase;

final class IpxRendererSnapshotTest extends RendererSnapshotTestCase
{
    public static function provideOptions(): iterable
    {
        yield 'constrained layout, both dimensions' => [
            new IpxProvider('images.example.com'),
            new RenderOptions(layout: Layout::Constrained, width: 800, height: 450),
        ];

        yield 'full-width layout, both dimensions' => [
            new IpxProvider('images.example.com'),
            new RenderOptions(layout: Layout::FullWidth, width: 800, height: 450),
        ];

        yield 'an IPX-specific operation' => [
            new IpxProvider('images.example.com'),
            new RenderOptions(layout: Layout::Constrained, width: 800, height: 450, operations: ['ipx' => ['grayscale' => true]]),
        ];
    }
}
