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
use Symfony\UX\CalendarLink\CalendarRecurrence;
use Symfony\UX\CalendarLink\Exception\InvalidArgumentException;

final class CalendarRecurrenceTest extends TestCase
{
    /**
     * @return iterable<string, array{CalendarRecurrence, string}>
     */
    public static function factoryProvider(): iterable
    {
        yield 'minutely default' => [CalendarRecurrence::minutely(), 'FREQ=MINUTELY'];
        yield 'daily default' => [CalendarRecurrence::daily(), 'FREQ=DAILY'];
        yield 'weekly default' => [CalendarRecurrence::weekly(), 'FREQ=WEEKLY'];
        yield 'monthly default' => [CalendarRecurrence::monthly(), 'FREQ=MONTHLY'];
        yield 'yearly default' => [CalendarRecurrence::yearly(), 'FREQ=YEARLY'];
        yield 'weekly count' => [CalendarRecurrence::weekly(count: 10), 'FREQ=WEEKLY;COUNT=10'];
        yield 'daily interval' => [CalendarRecurrence::daily(interval: 2), 'FREQ=DAILY;INTERVAL=2'];
        yield 'monthly interval + count' => [CalendarRecurrence::monthly(interval: 3, count: 4), 'FREQ=MONTHLY;INTERVAL=3;COUNT=4'];
    }

    #[DataProvider('factoryProvider')]
    public function testFactoriesProduceExpectedRrule(CalendarRecurrence $recurrence, string $expected)
    {
        $this->assertSame($expected, $recurrence->rrule);
    }

    public function testUntilIsFormattedAsUtc()
    {
        $until = new \DateTimeImmutable('2026-12-31 09:00:00', new \DateTimeZone('Europe/Paris'));

        $this->assertSame('FREQ=WEEKLY;UNTIL=20261231T080000Z', CalendarRecurrence::weekly(until: $until)->rrule);
    }

    /**
     * @return iterable<string, array{callable(): CalendarRecurrence}>
     */
    public static function invalidArgumentsProvider(): iterable
    {
        yield 'count and until together' => [static fn () => CalendarRecurrence::weekly(count: 5, until: new \DateTimeImmutable('2026-12-31'))];
        yield 'non-positive interval' => [static fn () => CalendarRecurrence::daily(interval: 0)];
        yield 'non-positive count' => [static fn () => CalendarRecurrence::daily(count: 0)];
    }

    #[DataProvider('invalidArgumentsProvider')]
    public function testRejectsInvalidArguments(callable $build)
    {
        $this->expectException(InvalidArgumentException::class);

        $build();
    }
}
