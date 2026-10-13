<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide\Tests;

use Symfony\UX\Image\Bridge\Glide\GlideProvider;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Renderer\RenderOptions;
use Symfony\UX\Image\Test\RendererSnapshotTestCase;

final class GlideRendererSnapshotTest extends RendererSnapshotTestCase
{
    public static function provideOptions(): iterable
    {
        yield 'constrained layout, both dimensions' => [
            new GlideProvider(null, '/img'),
            new RenderOptions(layout: Layout::Constrained, width: 800, height: 450),
        ];

        yield 'full-width layout, both dimensions' => [
            new GlideProvider(null, '/img'),
            new RenderOptions(layout: Layout::FullWidth, width: 800, height: 450),
        ];

        yield 'a Glide-specific operation' => [
            new GlideProvider(null, '/img'),
            new RenderOptions(layout: Layout::Constrained, width: 800, height: 450, operations: ['glide' => ['filt' => 'greyscale']]),
        ];

        yield 'signed URLs on an image host' => [
            new GlideProvider('images.example.com', '/img', 'mysecret'),
            new RenderOptions(layout: Layout::Fixed, width: 400),
        ];
    }
}
