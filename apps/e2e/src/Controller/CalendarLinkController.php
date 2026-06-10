<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\CalendarLink\CalendarEvent;
use Symfony\UX\CalendarLink\CalendarRecurrence;
use Symfony\UX\CalendarLink\CalendarReminder;

#[Route('/ux-calendar-link', name: 'app_ux_calendar_link_')]
final class CalendarLinkController extends AbstractController
{
    #[Route('/basic', name: 'basic')]
    public function basic(): Response
    {
        $event = new CalendarEvent(
            title: 'Symfony UX Conf',
            start: new \DateTimeImmutable('2030-06-15 10:00:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2030-06-15 11:00:00', new \DateTimeZone('UTC')),
            description: 'Annual conference',
            location: 'Paris, FR',
        );

        return $this->render('ux_calendar_link/basic.html.twig', ['event' => $event]);
    }

    #[Route('/all-day', name: 'all_day')]
    public function allDay(): Response
    {
        $event = new CalendarEvent(
            title: 'Company Holiday',
            start: new \DateTimeImmutable('2030-06-15 00:00:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2030-06-15 00:00:00', new \DateTimeZone('UTC')),
            location: 'Worldwide',
            allDay: true,
        );

        return $this->render('ux_calendar_link/all_day.html.twig', ['event' => $event]);
    }

    #[Route('/recurrence', name: 'recurrence')]
    public function recurrence(): Response
    {
        $event = new CalendarEvent(
            title: 'Daily Standup',
            start: new \DateTimeImmutable('2030-06-15 09:00:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2030-06-15 09:15:00', new \DateTimeZone('UTC')),
            recurrence: CalendarRecurrence::daily(count: 5),
        );

        return $this->render('ux_calendar_link/recurrence.html.twig', ['event' => $event]);
    }

    #[Route('/reminders', name: 'reminders')]
    public function reminders(): Response
    {
        $event = new CalendarEvent(
            title: 'Dentist Appointment',
            start: new \DateTimeImmutable('2030-06-15 14:00:00', new \DateTimeZone('UTC')),
            end: new \DateTimeImmutable('2030-06-15 15:00:00', new \DateTimeZone('UTC')),
            reminders: [
                CalendarReminder::before(minutes: 15),
                CalendarReminder::before(hours: 1),
            ],
        );

        return $this->render('ux_calendar_link/reminders.html.twig', ['event' => $event]);
    }
}
