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
use Symfony\UX\CalendarLink\Provider\GoogleCalendarLinkProvider;

final class GoogleCalendarLinkProviderTest extends TestCase
{
    public function testTimedEvent()
    {
        $provider = new GoogleCalendarLinkProvider();
        $event = new CalendarEvent(
            title: 'Symfony Live',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 18:00', new \DateTimeZone('UTC')),
            description: 'Annual conference',
            location: 'Paris',
        );

        $link = $provider->generate($event);

        $this->assertSame('google', $link->provider);
        $this->assertStringStartsWith('https://calendar.google.com/calendar/render?', $link->url);

        parse_str(parse_url($link->url, \PHP_URL_QUERY), $params);

        $this->assertSame('Symfony Live', $params['text']);
        $this->assertSame('20260514T090000Z/20260514T180000Z', $params['dates']);
        $this->assertSame('Paris', $params['location']);
    }

    public function testAllDayEventEndIsExclusive()
    {
        $provider = new GoogleCalendarLinkProvider();
        $event = new CalendarEvent(
            title: 'Holiday',
            start: new \DateTimeImmutable('2026-07-14', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-07-14', new \DateTimeZone('UTC')),
            allDay: true,
        );

        $link = $provider->generate($event);
        parse_str(parse_url($link->url, \PHP_URL_QUERY), $params);

        $this->assertSame('20260714/20260715', $params['dates']);
    }
}
