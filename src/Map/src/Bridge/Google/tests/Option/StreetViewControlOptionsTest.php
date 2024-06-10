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
use Symfony\UX\Map\Bridge\Google\Option\StreetViewControlOptions;

class StreetViewControlOptionsTest extends TestCase
{
    public function testToArray(): void
    {
        $options = new StreetViewControlOptions(
            position: ControlPosition::INLINE_END_BLOCK_CENTER
        );

        self::assertSame([
            'position' => ControlPosition::INLINE_END_BLOCK_CENTER->value,
        ], $options->toArray());
    }
}
