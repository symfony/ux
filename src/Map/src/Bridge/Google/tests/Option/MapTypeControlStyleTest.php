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
use Symfony\UX\Map\Bridge\Google\Option\MapTypeControlStyle;

class MapTypeControlStyleTest extends TestCase
{
    public function testEnumValues(): void
    {
        self::assertSame(0, MapTypeControlStyle::DEFAULT->value);
        self::assertSame(2, MapTypeControlStyle::DROPDOWN_MENU->value);
        self::assertSame(1, MapTypeControlStyle::HORIZONTAL_BAR->value);
    }
}
