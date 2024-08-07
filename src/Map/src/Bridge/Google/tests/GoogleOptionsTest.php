<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Google\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Bridge\Google\GoogleOptions;
use Symfony\UX\Map\Bridge\Google\Option\ControlPosition;
use Symfony\UX\Map\Bridge\Google\Option\GestureHandling;
use Symfony\UX\Map\Bridge\Google\Option\MapTypeControlStyle;

class GoogleOptionsTest extends TestCase
{
    public function testWithMinimalConfiguration(): void
    {
        $options = new GoogleOptions();

        self::assertSame([
            'mapId' => null,
            'gestureHandling' => 'auto',
            'backgroundColor' => null,
            'disableDoubleClickZoom' => false,
            'zoomControlOptions' => [
                'position' => ControlPosition::INLINE_END_BLOCK_END->value,
            ],
            'mapTypeControlOptions' => [
                'mapTypeIds' => [],
                'position' => ControlPosition::BLOCK_START_INLINE_START->value,
                'style' => MapTypeControlStyle::DEFAULT->value,
            ],
            'streetViewControlOptions' => [
                'position' => ControlPosition::INLINE_END_BLOCK_END->value,
            ],
            'fullscreenControlOptions' => [
                'position' => ControlPosition::INLINE_END_BLOCK_START->value,
            ],
        ], $options->toArray());
    }

    public function testWithMinimalConfigurationAndWithoutControls(): void
    {
        $options = new GoogleOptions(
            mapId: '2b2d73ba4b8c7b41',
            gestureHandling: GestureHandling::GREEDY,
            backgroundColor: '#f00',
            disableDoubleClickZoom: true,
            zoomControl: false,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        );

        self::assertSame([
            'mapId' => '2b2d73ba4b8c7b41',
            'gestureHandling' => GestureHandling::GREEDY->value,
            'backgroundColor' => '#f00',
            'disableDoubleClickZoom' => true,
        ], $options->toArray());
    }
}
