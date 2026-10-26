<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Imagor\Tests;

use Symfony\UX\Image\Bridge\Imagor\ImagorProvider;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Renderer\RenderOptions;
use Symfony\UX\Image\Test\RendererSnapshotTestCase;

final class ImagorRendererSnapshotTest extends RendererSnapshotTestCase
{
    public static function provideOptions(): iterable
    {
        yield 'constrained layout, both dimensions' => [
            new ImagorProvider('https://images.example.com'),
            new RenderOptions(layout: Layout::Constrained, width: 800, height: 450),
        ];

        yield 'full-width layout, both dimensions' => [
            new ImagorProvider('https://images.example.com'),
            new RenderOptions(layout: Layout::FullWidth, width: 800, height: 450),
        ];

        yield 'an imagor-specific operation' => [
            new ImagorProvider('https://images.example.com'),
            new RenderOptions(layout: Layout::Constrained, width: 800, height: 450, operations: ['imagor' => ['grayscale' => true]]),
        ];
    }
}
