<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\CalendarLink\Tests\Ics;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Uid\Factory\MockUuidFactory;
use Symfony\UX\CalendarLink\CalendarEvent;
use Symfony\UX\CalendarLink\CalendarRecurrence;
use Symfony\UX\CalendarLink\CalendarReminder;
use Symfony\UX\CalendarLink\Ics\IcsBuilder;

final class IcsBuilderTest extends TestCase
{
    private IcsBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new IcsBuilder(
            new MockUuidFactory(['0192a5d2-7c6f-7000-8000-000000000000']),
            new MockClock(new \DateTimeImmutable('2026-05-14 08:30:00', new \DateTimeZone('UTC'))),
        );
    }

    public function testDtstampUsesTheInjectedClock()
    {
        $event = new CalendarEvent(
            title: 'Demo',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 10:00', new \DateTimeZone('UTC')),
        );

        $this->assertStringContainsString("DTSTAMP:20260514T083000Z\r\n", $this->builder->build($event));
    }

    public function testUidIsUniquePerBuild()
    {
        $builder = new IcsBuilder();

        $event = new CalendarEvent(
            title: 'Demo',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 10:00', new \DateTimeZone('UTC')),
        );

        preg_match('/^UID:(.+)$/m', $builder->build($event), $first);
        preg_match('/^UID:(.+)$/m', $builder->build($event), $second);

        $this->assertNotSame($first[1], $second[1]);
    }

    public function testMinimalTimedEventStructure()
    {
        $event = new CalendarEvent(
            title: 'Demo',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 10:00', new \DateTimeZone('UTC')),
        );

        $ics = $this->builder->build($event);

        $this->assertStringContainsString(
            "DTSTART:20260514T090000Z\r\n"
            ."DTEND:20260514T100000Z\r\n"
            ."SUMMARY:Demo\r\n"
            ."END:VEVENT\r\n"
            ."END:VCALENDAR\r\n",
            $ics,
        );
    }

    public function testAllDayEventUsesValueDateAndIncrementsEnd()
    {
        $event = new CalendarEvent(
            title: 'Conf',
            start: new \DateTimeImmutable('2026-05-14', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-15', new \DateTimeZone('UTC')),
            allDay: true,
        );

        $ics = $this->builder->build($event);

        $this->assertStringContainsString("DTSTART;VALUE=DATE:20260514\r\n", $ics);
        $this->assertStringContainsString("DTEND;VALUE=DATE:20260516\r\n", $ics);
    }

    public function testTimedEventInNamedZoneUsesTzidInsteadOfUtc()
    {
        $event = new CalendarEvent(
            title: 'Standup',
            start: new \DateTimeImmutable('2026-07-01 09:00', new \DateTimeZone('Europe/Paris')),
            end: new \DateTimeImmutable('2026-07-01 09:30', new \DateTimeZone('Europe/Paris')),
        );

        $ics = $this->builder->build($event);

        $this->assertStringContainsString("DTSTART;TZID=Europe/Paris:20260701T090000\r\n", $ics);
        $this->assertStringContainsString("DTEND;TZID=Europe/Paris:20260701T093000\r\n", $ics);
    }

    public function testNamedZoneEmitsVtimezoneWithDstRules()
    {
        $event = new CalendarEvent(
            title: 'Standup',
            start: new \DateTimeImmutable('2026-07-01 09:00', new \DateTimeZone('Europe/Paris')),
            end: new \DateTimeImmutable('2026-07-01 09:30', new \DateTimeZone('Europe/Paris')),
            recurrence: CalendarRecurrence::weekly(),
        );

        $ics = $this->builder->build($event);

        $this->assertStringContainsString("BEGIN:VTIMEZONE\r\nTZID:Europe/Paris\r\n", $ics);
        $this->assertStringContainsString("BEGIN:DAYLIGHT\r\n", $ics);
        $this->assertStringContainsString("TZOFFSETTO:+0200\r\n", $ics);
        $this->assertStringContainsString("RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\n", $ics);
        $this->assertStringContainsString("BEGIN:STANDARD\r\n", $ics);
        $this->assertStringContainsString("TZOFFSETTO:+0100\r\n", $ics);
        $this->assertStringContainsString("RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU\r\n", $ics);
    }

    public function testUtcEventDoesNotEmitVtimezone()
    {
        $event = new CalendarEvent(
            title: 'Demo',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 10:00', new \DateTimeZone('UTC')),
        );

        $ics = $this->builder->build($event);

        $this->assertStringNotContainsString('BEGIN:VTIMEZONE', $ics);
        $this->assertStringContainsString("DTSTART:20260514T090000Z\r\n", $ics);
    }

    public function testTextEscaping()
    {
        $event = new CalendarEvent(
            title: 'Symfony, UX; test',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 10:00', new \DateTimeZone('UTC')),
            description: "Line 1\nLine 2",
            location: 'A\\B',
        );

        $ics = $this->builder->build($event);

        $this->assertStringContainsString("SUMMARY:Symfony\\, UX\\; test\r\n", $ics);
        $this->assertStringContainsString('DESCRIPTION:Line 1\nLine 2', $ics);
        $this->assertStringContainsString('LOCATION:A\\\\B', $ics);
    }

    public function testLineFoldingAtSeventyFiveOctets()
    {
        $event = new CalendarEvent(
            title: 'Demo',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 10:00', new \DateTimeZone('UTC')),
            description: str_repeat('a', 300),
        );

        $ics = $this->builder->build($event);

        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, \strlen($line), \sprintf('Line "%s" exceeds 75 octets.', $line));
        }
    }

    /**
     * @return iterable<string, array{CalendarReminder, string}>
     */
    public static function triggerFormatProvider(): iterable
    {
        yield 'minutes' => [CalendarReminder::before(minutes: 15), '-PT15M'];
        yield 'exact hours' => [CalendarReminder::before(hours: 2), '-PT2H'];
        yield 'exact days' => [CalendarReminder::before(days: 1), '-P1D'];
        yield 'exact weeks' => [CalendarReminder::before(weeks: 1), '-P1W'];
        yield 'mixed not divisible by 60' => [CalendarReminder::before(hours: 23, minutes: 88), '-PT1468M'];
        yield 'mixed hours and minutes' => [CalendarReminder::before(hours: 1, minutes: 30), '-PT90M'];
    }

    #[DataProvider('triggerFormatProvider')]
    public function testTriggerFormat(CalendarReminder $reminder, string $expectedTrigger)
    {
        $event = new CalendarEvent(
            title: 'Demo',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 10:00', new \DateTimeZone('UTC')),
            reminders: [$reminder],
        );

        $this->assertStringContainsString("TRIGGER:$expectedTrigger\r\n", $this->builder->build($event));
    }

    public function testValarmBlockFromReminders()
    {
        $event = new CalendarEvent(
            title: 'Demo',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 10:00', new \DateTimeZone('UTC')),
            reminders: [CalendarReminder::before(minutes: 15, description: 'Stand-up')],
        );

        $ics = $this->builder->build($event);

        $this->assertStringContainsString("BEGIN:VALARM\r\n", $ics);
        $this->assertStringContainsString("TRIGGER:-PT15M\r\n", $ics);
        $this->assertStringContainsString("END:VALARM\r\n", $ics);
    }

    public function testRrulePassthrough()
    {
        $event = new CalendarEvent(
            title: 'Weekly',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 10:00', new \DateTimeZone('UTC')),
            recurrence: CalendarRecurrence::weekly(count: 10),
        );

        $ics = $this->builder->build($event);

        $this->assertStringContainsString("RRULE:FREQ=WEEKLY;COUNT=10\r\n", $ics);
    }
}
