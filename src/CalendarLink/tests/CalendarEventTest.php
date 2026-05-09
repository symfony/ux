<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\CalendarLink\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\CalendarLink\CalendarEvent;
use Symfony\UX\CalendarLink\Exception\InvalidArgumentException;

final class CalendarEventTest extends TestCase
{
    public function testRejectsEmptyTitle()
    {
        $this->expectException(InvalidArgumentException::class);

        new CalendarEvent(
            title: '   ',
            start: new \DateTimeImmutable('2026-05-14 09:00'),
            end: new \DateTimeImmutable('2026-05-14 10:00'),
        );
    }

    public function testRejectsEndBeforeStart()
    {
        $this->expectException(InvalidArgumentException::class);

        new CalendarEvent(
            title: 'Demo',
            start: new \DateTimeImmutable('2026-05-14 10:00'),
            end: new \DateTimeImmutable('2026-05-14 09:00'),
        );
    }
}
