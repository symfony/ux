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
            'DTSTART:'.$this->formatUtc($event->start),
            'DTEND:'.$this->formatUtc($event->end),
        ];
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
