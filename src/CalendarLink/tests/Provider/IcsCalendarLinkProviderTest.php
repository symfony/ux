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
use Symfony\UX\CalendarLink\Ics\IcsBuilder;
use Symfony\UX\CalendarLink\Provider\IcsCalendarLinkProvider;

final class IcsCalendarLinkProviderTest extends TestCase
{
    public function testReturnsDataUri()
    {
        $provider = new IcsCalendarLinkProvider(new IcsBuilder());
        $event = new CalendarEvent(
            title: 'Demo',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 10:00', new \DateTimeZone('UTC')),
        );

        $link = $provider->generate($event);

        $this->assertSame('ics', $link->provider);
        $this->assertStringStartsWith('data:text/calendar;charset=utf-8;base64,', $link->url);

        $payload = base64_decode(substr($link->url, \strlen('data:text/calendar;charset=utf-8;base64,')), true);
        $this->assertIsString($payload);
        $this->assertStringContainsString('BEGIN:VCALENDAR', $payload);
        $this->assertStringContainsString('SUMMARY:Demo', $payload);
    }
}
