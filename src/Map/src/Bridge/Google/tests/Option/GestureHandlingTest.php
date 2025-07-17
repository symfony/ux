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
use Symfony\UX\Map\Bridge\Google\Option\GestureHandling;

class GestureHandlingTest extends TestCase
{
    public function testEnumValues(): void
    {
        self::assertSame('cooperative', GestureHandling::COOPERATIVE->value);
        self::assertSame('greedy', GestureHandling::GREEDY->value);
        self::assertSame('none', GestureHandling::NONE->value);
        self::assertSame('auto', GestureHandling::AUTO->value);
    }
}
