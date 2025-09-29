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
use Symfony\UX\Map\Bridge\Leaflet\Option\AttributionControlOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\ControlPosition;

class AttributionControlOptionsTest extends TestCase
{
    public function testToArray()
    {
        $options = new AttributionControlOptions();

        self::assertSame([
            'position' => ControlPosition::BOTTOM_RIGHT->value,
            'prefix' => 'Leaflet',
        ], $options->toArray());
    }

    public function testToArrayWithDifferentConfiguration()
    {
        $options = new AttributionControlOptions(
            position: ControlPosition::BOTTOM_LEFT,
            prefix: 'Leaflet prefix',
        );

        self::assertSame([
            'position' => ControlPosition::BOTTOM_LEFT->value,
            'prefix' => 'Leaflet prefix',
        ], $options->toArray());
    }
}
