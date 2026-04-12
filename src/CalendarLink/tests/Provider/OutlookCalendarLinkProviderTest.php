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
use Symfony\UX\CalendarLink\Provider\OutlookCalendarLinkProvider;

final class OutlookCalendarLinkProviderTest extends TestCase
{
    public function testOutlookComTimedEvent()
    {
        $provider = new OutlookCalendarLinkProvider();
        $event = new CalendarEvent(
            title: 'Symfony Live',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 18:00', new \DateTimeZone('UTC')),
            location: 'Paris',
        );

        $link = $provider->generate($event);

        $this->assertSame('outlook', $link->provider);
        $this->assertStringStartsWith('https://outlook.live.com/calendar/0/deeplink/compose?', $link->url);

        parse_str(parse_url($link->url, \PHP_URL_QUERY), $params);

        $this->assertSame('Symfony Live', $params['subject']);
        $this->assertSame('2026-05-14T09:00:00Z', $params['startdt']);
        $this->assertSame('2026-05-14T18:00:00Z', $params['enddt']);
    }

    public function testAllDayEvent()
    {
        $provider = new OutlookCalendarLinkProvider();
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
