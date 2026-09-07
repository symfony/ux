<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Google\Tests\Option;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Bridge\Google\Option\ControlPosition;
use Symfony\UX\Map\Bridge\Google\Option\MapTypeControlOptions;
use Symfony\UX\Map\Bridge\Google\Option\MapTypeControlStyle;

class MapTypeControlOptionsTest extends TestCase
{
    public function testToArray(): void
    {
        $options = new MapTypeControlOptions(
            mapTypeIds: ['satellite', 'hybrid'],
            position: ControlPosition::BLOCK_END_INLINE_END,
            style: MapTypeControlStyle::HORIZONTAL_BAR,
        );

        self::assertSame([
            'mapTypeIds' => ['satellite', 'hybrid'],
            'position' => ControlPosition::BLOCK_END_INLINE_END->value,
            'style' => MapTypeControlStyle::HORIZONTAL_BAR->value,
        ], $options->toArray());
    }
}
