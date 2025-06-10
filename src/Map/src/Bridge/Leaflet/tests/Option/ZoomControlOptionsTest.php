<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Leaflet\Tests\Option;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Bridge\Leaflet\Option\ControlPosition;
use Symfony\UX\Map\Bridge\Leaflet\Option\ZoomControlOptions;

class ZoomControlOptionsTest extends TestCase
{
    public function testToArray(): void
    {
        $options = new ZoomControlOptions(
            position: ControlPosition::TOP_LEFT,
        );

        self::assertSame([
            'position' => ControlPosition::TOP_LEFT->value,
            'zoomInText' => '<span aria-hidden="true">+</span>',
            'zoomInTitle' => 'Zoom in',
            'zoomOutText' => '<span aria-hidden="true">&#x2212;</span>',
            'zoomOutTitle' => 'Zoom out',
        ], $options->toArray());
    }
}
