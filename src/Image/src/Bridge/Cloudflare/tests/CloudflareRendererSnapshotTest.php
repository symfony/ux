<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Cloudflare\Tests;

use Symfony\UX\Image\Bridge\Cloudflare\CloudflareProvider;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Renderer\RenderOptions;
use Symfony\UX\Image\Test\RendererSnapshotTestCase;

/**
 * Pins the URL matrix a real Cloudflare zone produces, complementing {@see CloudflareProviderTest}'s
 * single-URL assertions with the full per-breakpoint srcset shape.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class CloudflareRendererSnapshotTest extends RendererSnapshotTestCase
{
    public static function provideOptions(): iterable
    {
        yield 'constrained layout, both dimensions' => [
            new CloudflareProvider('cdn.example.com'),
            new RenderOptions(layout: Layout::Constrained, width: 800, height: 450),
        ];

        yield 'full-width layout, both dimensions' => [
            new CloudflareProvider('cdn.example.com'),
            new RenderOptions(layout: Layout::FullWidth, width: 800, height: 450),
        ];

        yield 'a Cloudflare-specific operation' => [
            new CloudflareProvider('cdn.example.com'),
            new RenderOptions(layout: Layout::Constrained, width: 800, height: 450, operations: ['cloudflare' => ['gravity' => 'auto']]),
        ];
    }
}
