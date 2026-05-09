<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\CalendarLink\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Symfony\UX\CalendarLink\CalendarEvent;
use Symfony\UX\CalendarLink\Provider\Office365CalendarLinkProvider;

final class Office365CalendarLinkProviderTest extends TestCase
{
    public function testOffice365TimedEvent()
    {
        $provider = new Office365CalendarLinkProvider();
        $event = new CalendarEvent(
            title: 'Standup',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 09:15', new \DateTimeZone('UTC')),
            location: 'Paris',
        );

        $link = $provider->generate($event);

        $this->assertSame('office365', $link->provider);
        $this->assertStringStartsWith('https://outlook.office.com/calendar/0/deeplink/compose?', $link->url);

        parse_str(parse_url($link->url, \PHP_URL_QUERY), $params);

        $this->assertSame('Standup', $params['subject']);
        $this->assertSame('2026-05-14T09:00:00Z', $params['startdt']);
        $this->assertSame('2026-05-14T09:15:00Z', $params['enddt']);
        $this->assertSame('Paris', $params['location']);
    }

    public function testAllDayEvent()
    {
        $provider = new Office365CalendarLinkProvider();
        $event = new CalendarEvent(
            title: 'Bastille Day',
            start: new \DateTimeImmutable('2026-07-14', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-07-14', new \DateTimeZone('UTC')),
            allDay: true,
        );

        $link = $provider->generate($event);

        parse_str(parse_url($link->url, \PHP_URL_QUERY), $params);

        $this->assertSame('true', $params['allday']);
        $this->assertSame('2026-07-14', $params['startdt']);
        $this->assertSame('2026-07-14', $params['enddt']);
    }
}
