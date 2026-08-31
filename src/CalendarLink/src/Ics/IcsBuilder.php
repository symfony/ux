<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\CalendarLink\Ics;

use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\UX\CalendarLink\CalendarEvent;
use Symfony\UX\CalendarLink\CalendarReminder;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 *
 * @internal
 */
final class IcsBuilder
{
    private const CRLF = "\r\n";
    private const PRODID = '-//Symfony//UX Calendar Link//EN';
    private const TWO_YEARS = 63072000;

    private readonly UuidFactory $uuidFactory;

    public function __construct(?UuidFactory $uuidFactory = null)
    {
        $this->uuidFactory = $uuidFactory ?? new UuidFactory();
    }

    public function build(CalendarEvent $event): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:'.self::PRODID,
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            ...$this->formatVtimezones($event),
            'BEGIN:VEVENT',
            'UID:'.$this->uuidFactory->create()->toRfc4122(),
            'DTSTAMP:'.$this->formatUtc(new \DateTimeImmutable('now', new \DateTimeZone('UTC'))),
            ...$this->formatDateLines($event),
            'SUMMARY:'.$this->escape($event->title),
        ];

        if (null !== $event->description && '' !== $event->description) {
            $lines[] = 'DESCRIPTION:'.$this->escape($event->description);
        }

        if (null !== $event->location && '' !== $event->location) {
            $lines[] = 'LOCATION:'.$this->escape($event->location);
        }

        if (null !== $event->url && '' !== $event->url) {
            $lines[] = 'URL:'.$event->url;
        }

        if (null !== $event->recurrence) {
            $lines[] = 'RRULE:'.$event->recurrence->rrule;
        }

        foreach ($event->reminders as $reminder) {
            array_push($lines, ...$this->formatAlarm($reminder));
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        $folded = array_map([$this, 'fold'], $lines);

        return implode(self::CRLF, $folded).self::CRLF;
    }

    /**
     * @return list<string>
     */
    private function formatDateLines(CalendarEvent $event): array
    {
        if ($event->allDay) {
            // DTEND is exclusive for all-day events: the user-visible last day
            // must be incremented by one.
            $start = $event->start->setTime(0, 0, 0);
            $end = $event->end->setTime(0, 0, 0)->modify('+1 day');

            return [
                'DTSTART;VALUE=DATE:'.$start->format('Ymd'),
                'DTEND;VALUE=DATE:'.$end->format('Ymd'),
            ];
        }

        return [
            $this->formatTimedLine('DTSTART', $event->start),
            $this->formatTimedLine('DTEND', $event->end),
        ];
    }

    /**
     * A region time zone is serialized as local time anchored with `;TZID=`, so a recurring
     * local time (e.g. 09:00) stays fixed across DST instead of drifting by an hour. UTC and
     * fixed offsets carry no DST, so the lossless `...Z` form is kept.
     */
    private function formatTimedLine(string $property, \DateTimeImmutable $dt): string
    {
        $tz = $dt->getTimezone();

        if ($this->isRegionZone($tz)) {
            return $property.';TZID='.$tz->getName().':'.$dt->format('Ymd\THis');
        }

        return $property.':'.$this->formatUtc($dt);
    }

    /**
     * @return list<string>
     */
    private function formatVtimezones(CalendarEvent $event): array
    {
        $lines = [];
        foreach ($this->timezones($event) as $timezone) {
            array_push($lines, ...$this->formatVtimezone($timezone, $event->start));
        }

        return $lines;
    }

    /**
     * @return list<\DateTimeZone>
     */
    private function timezones(CalendarEvent $event): array
    {
        if ($event->allDay) {
            return [];
        }

        $timezones = [];
        foreach ([$event->start->getTimezone(), $event->end->getTimezone()] as $tz) {
            if ($this->isRegionZone($tz)) {
                $timezones[$tz->getName()] = $tz;
            }
        }

        return array_values($timezones);
    }

    private function isRegionZone(\DateTimeZone $tz): bool
    {
        $name = $tz->getName();

        // UTC and fixed numeric offsets (e.g. "+02:00") carry no DST, so no VTIMEZONE is needed.
        return 'UTC' !== $name && !preg_match('/^[+-]\d{2}:\d{2}$/', $name);
    }

    /**
     * @return list<string>
     */
    private function formatVtimezone(\DateTimeZone $tz, \DateTimeImmutable $reference): array
    {
        $lines = ['BEGIN:VTIMEZONE', 'TZID:'.$tz->getName()];

        foreach ($this->timezoneRules($tz, $reference) as $rule) {
            array_push($lines, ...$rule);
        }

        $lines[] = 'END:VTIMEZONE';

        return $lines;
    }

    /**
     * The STANDARD and DAYLIGHT rules in effect around $reference, each expressed as a yearly
     * recurring transition so the VTIMEZONE stays valid for open-ended recurrences.
     *
     * @return list<list<string>>
     */
    private function timezoneRules(\DateTimeZone $tz, \DateTimeImmutable $reference): array
    {
        $refTs = $reference->getTimestamp();
        $transitions = $tz->getTransitions($refTs - self::TWO_YEARS, $refTs + self::TWO_YEARS);

        // Keep the most recent STANDARD and DAYLIGHT transitions; they define the yearly rule.
        $latest = [];
        for ($i = 1, $count = \count($transitions); $i < $count; ++$i) {
            $type = $transitions[$i]['isdst'] ? 'DAYLIGHT' : 'STANDARD';
            $latest[$type] = [$transitions[$i], $transitions[$i - 1]];
        }

        if ([] === $latest) {
            // A zone without DST transitions: a single fixed STANDARD offset.
            return [$this->timezoneRule('STANDARD', $transitions[0], $transitions[0], false)];
        }

        $rules = [];
        foreach ($latest as $type => [$transition, $previous]) {
            $rules[] = $this->timezoneRule($type, $transition, $previous, true);
        }

        return $rules;
    }

    /**
     * @param array{ts: int, time: string, offset: int, isdst: bool, abbr: string} $transition
     * @param array{ts: int, time: string, offset: int, isdst: bool, abbr: string} $previous
     *
     * @return list<string>
     */
    private function timezoneRule(string $type, array $transition, array $previous, bool $recurring): array
    {
        $onset = $transition['ts'] + $previous['offset'];

        $lines = [
            'BEGIN:'.$type,
            'DTSTART:'.gmdate('Ymd\THis', $onset),
            'TZOFFSETFROM:'.$this->formatOffset($previous['offset']),
            'TZOFFSETTO:'.$this->formatOffset($transition['offset']),
            'TZNAME:'.$transition['abbr'],
        ];

        if ($recurring) {
            $lines[] = 'RRULE:'.$this->yearlyRule($onset);
        }

        $lines[] = 'END:'.$type;

        return $lines;
    }

    private function yearlyRule(int $onset): string
    {
        $days = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
        $dayOfMonth = (int) gmdate('j', $onset);
        $ordinal = $dayOfMonth + 7 > (int) gmdate('t', $onset) ? -1 : (int) ceil($dayOfMonth / 7);

        return \sprintf('FREQ=YEARLY;BYMONTH=%d;BYDAY=%d%s', (int) gmdate('n', $onset), $ordinal, $days[(int) gmdate('w', $onset)]);
    }

    private function formatOffset(int $seconds): string
    {
        $sign = $seconds < 0 ? '-' : '+';
        $seconds = abs($seconds);

        return \sprintf('%s%02d%02d', $sign, intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }

    /**
     * @return list<string>
     */
    private function formatAlarm(CalendarReminder $reminder): array
    {
        return [
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            'TRIGGER:'.$this->formatTrigger($reminder->minutesBefore),
            'DESCRIPTION:'.$this->escape('' !== $reminder->description ? $reminder->description : 'Reminder'),
            'END:VALARM',
        ];
    }

    private function formatTrigger(int $minutesBefore): string
    {
        if (0 === $minutesBefore % 10080) {
            return '-P'.($minutesBefore / 10080).'W';
        }
        if (0 === $minutesBefore % 1440) {
            return '-P'.($minutesBefore / 1440).'D';
        }
        if (0 === $minutesBefore % 60) {
            return '-PT'.($minutesBefore / 60).'H';
        }

        return '-PT'.$minutesBefore.'M';
    }

    private function formatUtc(\DateTimeInterface $dt): string
    {
        return \DateTimeImmutable::createFromInterface($dt)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Ymd\THis\Z');
    }

    private function escape(string $text): string
    {
        $text = str_replace(['\\', ',', ';'], ['\\\\', '\\,', '\\;'], $text);

        return str_replace(["\r\n", "\r", "\n"], '\\n', $text);
    }

    private function fold(string $line): string
    {
        if (\strlen($line) <= 75) {
            return $line;
        }

        $result = mb_strcut($line, 0, 75, 'UTF-8');
        $offset = \strlen($result);

        while ($offset < \strlen($line)) {
            $chunk = mb_strcut($line, $offset, 74, 'UTF-8');
            $result .= self::CRLF.' '.$chunk;
            $offset += \strlen($chunk);
        }

        return $result;
    }
}
