<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\CalendarLink\Provider;

use Symfony\UX\CalendarLink\CalendarEvent;
use Symfony\UX\CalendarLink\CalendarLink;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 */
final class GoogleCalendarLinkProvider implements CalendarLinkProviderInterface
{
    private const BASE_URL = 'https://calendar.google.com/calendar/render';

    public function getName(): string
    {
        return 'google';
    }

    public function getLabel(): string
    {
        return 'Google Calendar';
    }

    public function generate(CalendarEvent $event): CalendarLink
    {
        $params = [
            'action' => 'TEMPLATE',
            'text' => $event->title,
            'dates' => $this->formatDates($event),
        ];

        if (null !== $event->description && '' !== $event->description) {
            $params['details'] = $event->description;
        }

        if (null !== $event->location && '' !== $event->location) {
            $params['location'] = $event->location;
        }

        if (null !== $event->recurrence) {
            // Google expects the literal "RRULE:" prefix.
            $params['recur'] = 'RRULE:'.$event->recurrence->rrule;
        }

        // Google Calendar deeplinks do not support reminders; $event->reminders is intentionally ignored.

        $url = self::BASE_URL.'?'.http_build_query($params, '', '&', \PHP_QUERY_RFC3986);

        return new CalendarLink($this->getName(), $this->getLabel(), $url);
    }

    private function formatDates(CalendarEvent $event): string
    {
        if ($event->allDay) {
            // Google all-day format: YYYYMMDD/YYYYMMDD (end exclusive).
            $start = $event->start->format('Ymd');
            $end = $event->end->modify('+1 day')->format('Ymd');

            return $start.'/'.$end;
        }

        $start = $event->start->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');
        $end = $event->end->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');

        return $start.'/'.$end;
    }
}
