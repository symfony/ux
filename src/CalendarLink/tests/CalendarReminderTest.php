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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\CalendarLink\CalendarReminder;
use Symfony\UX\CalendarLink\Exception\InvalidArgumentException;

final class CalendarReminderTest extends TestCase
{
    /**
     * @return iterable<string, array{CalendarReminder, int}>
     */
    public static function beforeProvider(): iterable
    {
        yield 'minutes' => [CalendarReminder::before(minutes: 15), 15];
        yield 'hours' => [CalendarReminder::before(hours: 2), 120];
        yield 'days' => [CalendarReminder::before(days: 1), 1440];
        yield 'weeks' => [CalendarReminder::before(weeks: 1), 10080];
        yield 'hours and minutes (additive)' => [CalendarReminder::before(hours: 1, minutes: 30), 90];
    }

    #[DataProvider('beforeProvider')]
    public function testBeforeProducesExpectedMinutes(CalendarReminder $reminder, int $expected)
    {
        $this->assertSame($expected, $reminder->minutesBefore);
    }

    public function testDescriptionIsKept()
    {
        $this->assertSame('Leave early', CalendarReminder::before(hours: 2, description: 'Leave early')->description);
    }

    public function testRejectsZeroTotal()
    {
        $this->expectException(InvalidArgumentException::class);

        CalendarReminder::before();
    }

    public function testRejectsNegativeArgument()
    {
        $this->expectException(InvalidArgumentException::class);

        CalendarReminder::before(minutes: -5);
    }
}
