<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\KeyCdn\Tests;

use Symfony\UX\Image\Bridge\KeyCdn\KeyCdnProvider;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Renderer\RenderOptions;
use Symfony\UX\Image\Test\RendererSnapshotTestCase;

/**
 * Pins the URL matrix a real KeyCDN zone produces, complementing {@see KeyCdnProviderTest}'s
 * single-URL assertions with the full per-breakpoint srcset shape. KeyCDN never negotiates a
 * format itself, so every case below produces a <picture> with one <source> per configured
 * format, not a plain <img>.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class KeyCdnRendererSnapshotTest extends RendererSnapshotTestCase
{
    public static function provideOptions(): iterable
    {
        yield 'constrained layout, both dimensions' => [
            new KeyCdnProvider('zone.kxcdn.com'),
            new RenderOptions(layout: Layout::Constrained, width: 800, height: 450),
        ];

        yield 'full-width layout, both dimensions' => [
            new KeyCdnProvider('zone.kxcdn.com'),
            new RenderOptions(layout: Layout::FullWidth, width: 800, height: 450),
        ];

        yield 'fixed layout, the <picture> sources at their most readable' => [
            new KeyCdnProvider('zone.kxcdn.com'),
            new RenderOptions(layout: Layout::Fixed, width: 400),
        ];
    }
}
