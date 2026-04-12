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
abstract class AbstractOutlookCalendarLinkProvider implements CalendarLinkProviderInterface
{
    abstract protected function getBaseUrl(): string;

    public function generate(CalendarEvent $event): CalendarLink
    {
        $params = [
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => $event->title,
            'startdt' => $this->formatDate($event->start, $event->allDay),
            'enddt' => $this->formatDate($event->end, $event->allDay),
        ];

        if ($event->allDay) {
            $params['allday'] = 'true';
        }

        if (null !== $event->description && '' !== $event->description) {
            $params['body'] = $event->description;
        }

        if (null !== $event->location && '' !== $event->location) {
            $params['location'] = $event->location;
        }

        // Outlook deeplinks do not support recurrence or reminders; $event->recurrence and $event->reminders are intentionally ignored.

        $url = $this->getBaseUrl().'?'.http_build_query($params, '', '&', \PHP_QUERY_RFC3986);

        return new CalendarLink($this->getName(), $this->getLabel(), $url);
    }

    private function formatDate(\DateTimeImmutable $dt, bool $allDay): string
    {
        if ($allDay) {
            return $dt->format('Y-m-d');
        }

        return $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
