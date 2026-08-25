<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\CalendarLink\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\UX\CalendarLink\CalendarEvent;
use Symfony\UX\CalendarLink\Provider\CalendarLinkProviderInterface;
use Symfony\UX\CalendarLink\Registry\CalendarLinkProviderRegistry;

final class BundleIntegrationTest extends KernelTestCase
{
    public function testContainerCompilesAndRegistersAllProviders()
    {
        $container = self::getContainer();

        /** @var CalendarLinkProviderRegistry $registry */
        $registry = $container->get(CalendarLinkProviderRegistry::class);

        $this->assertSame(['google', 'outlook', 'office365', 'ics'], array_keys($registry->all()));

        foreach ($registry->all() as $provider) {
            $this->assertInstanceOf(CalendarLinkProviderInterface::class, $provider);
        }
    }

    public function testDtstampUsesTheContainerClock()
    {
        $container = self::getContainer();
        $container->set('clock', new MockClock(new \DateTimeImmutable('2026-05-14 08:30:00', new \DateTimeZone('UTC'))));

        $ics = $container->get('ux_calendar_link.ics.builder')->build(new CalendarEvent(
            title: 'Demo',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 10:00', new \DateTimeZone('UTC')),
        ));

        $this->assertStringContainsString("DTSTAMP:20260514T083000Z\r\n", $ics);
    }

    public function testTwigRendersCalendarLink()
    {
        $event = new CalendarEvent(
            title: 'Demo',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 10:00', new \DateTimeZone('UTC')),
        );

        $rendered = self::getContainer()->get('twig')
            ->createTemplate('{{- ux_calendar_link(event, \'google\').url }}')
            ->render(['event' => $event]);

        $this->assertStringStartsWith('https://calendar.google.com/calendar/render?', $rendered);
        $this->assertStringContainsString('text=Demo', $rendered);
    }

    public function testTwigRendersAllCalendarLinks()
    {
        $event = new CalendarEvent(
            title: 'Demo',
            start: new \DateTimeImmutable('2026-05-14 09:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2026-05-14 10:00', new \DateTimeZone('UTC')),
        );

        $rendered = self::getContainer()->get('twig')
            ->createTemplate('{{- ux_calendar_links(event)|keys|join(\',\') }}')
            ->render(['event' => $event]);

        $this->assertSame('google,outlook,office365,ics', $rendered);
    }
}
