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
use Symfony\UX\Map\Bridge\Google\Option\ZoomControlOptions;

class ZoomControlOptionsTest extends TestCase
{
    public function testToArray(): void
    {
        $options = new ZoomControlOptions(
            position: ControlPosition::BLOCK_START_INLINE_END,
        );

        self::assertSame([
            'position' => ControlPosition::BLOCK_START_INLINE_END->value,
        ], $options->toArray());
    }
}
