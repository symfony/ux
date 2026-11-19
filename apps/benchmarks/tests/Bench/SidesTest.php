<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Bench;

use App\Bench\Sides;
use PHPUnit\Framework\TestCase;

final class SidesTest extends TestCase
{
    public function testItRefusesTheSameRefTwice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The ref "v3.6.0" is given twice.');

        Sides::labels(['v3.6.0', 'v3.6.0'], '3.x (abc1234)');
    }

    public function testItRefusesTheWorkingTreeLabel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The ref "3.x (abc1234)" is the working tree.');

        Sides::labels(['3.x (abc1234)'], '3.x (abc1234)');
    }

    public function testItRefusesTwoRefsSharingADirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The refs "a/b" and "a-b" share the directory "a-b".');

        Sides::labels(['a/b', 'a-b'], '3.x (abc1234)');
    }

    public function testItOrdersAgainstSidesThenTheWorkingTree(): void
    {
        $labels = Sides::labels(['v3.6.0', 'v3.7.2'], '3.x (abc1234)');

        self::assertSame(['v3.6.0', 'v3.7.2', '3.x (abc1234)'], $labels);
    }
}
