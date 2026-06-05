Symfony UX Calendar Link
========================

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Symfony UX CalendarLink is a Symfony bundle that allows generation of "Add to calendar" links for Google
Calendar, Outlook.com, Office 365 and iCalendar (``.ics``), the format
consumed by Apple Calendar, Outlook desktop, Thunderbird and every native
calendar client.

Installation
------------

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-calendar-link

Usage
-----

Start by creating a ``CalendarEvent`` object in your controller. Once populated with your event details, pass it to Twig to render the calendar links::

    // src/Controller/EventController.php
    namespace App\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\UX\CalendarLink\CalendarEvent;

    final class EventController extends AbstractController
    {
        #[Route('/event/{id}', name: 'app_event_show')]
        public function show(): Response
        {
            // Build from your database, API, etc.
            $event = new CalendarEvent(
                title: 'Symfony Live Paris',
                start: new \DateTimeImmutable('2026-05-14 09:00'),
                end:   new \DateTimeImmutable('2026-05-15 18:00'),
                location: 'Cité Universitaire Paris',
                description: 'Annual Symfony conference in France',
            );

            return $this->render('event/show.html.twig', ['event' => $event]);
        }
    }

Then use the two Twig functions to render links:

* ``ux_calendar_link(event, provider)``: generates a link for one provider
* ``ux_calendar_links(event)``: generates links for every registered provider

.. code-block:: html+twig

    {# templates/event/show.html.twig #}
    <a href="{{ ux_calendar_link(event, 'google').url }}">Add to Google Calendar</a>

    <ul class="add-to-calendar">
        {% for link in ux_calendar_links(event) %}
            <li><a href="{{ link.url }}">{{ link.label }}</a></li>
        {% endfor %}
    </ul>

Supported providers
-------------------

========================= =========== ========== ============= =========
Field                     Google      Outlook    Office 365    ICS
========================= =========== ========== ============= =========
title                     yes         yes        yes           yes
start / end               yes         yes        yes           yes
description               yes         yes        yes           yes
location                  yes         yes        yes           yes
all-day                   yes         yes        yes           yes
reminders (VALARM)        -           -          -             yes
recurrence (RRULE)        yes         -          -             yes
========================= =========== ========== ============= =========

Fields marked ``-`` are silently ignored when generating a link for that
provider, because the provider's URL scheme does not support them. For example,
reminders are honoured only in the ``.ics`` output since Google and Outlook
deeplink URLs cannot carry VALARM data.

Reminders and recurrence
------------------------

Both recurrence rules and reminders can be attached when constructing the event::

    use Symfony\UX\CalendarLink\CalendarEvent;
    use Symfony\UX\CalendarLink\CalendarRecurrence;
    use Symfony\UX\CalendarLink\CalendarReminder;

    $event = new CalendarEvent(
        title: 'Weekly sync',
        start: new \DateTimeImmutable('2026-05-14 10:00'),
        end:   new \DateTimeImmutable('2026-05-14 10:30'),
        recurrence: CalendarRecurrence::weekly(count: 10),
        reminders: [CalendarReminder::before(minutes: 15)],
    );

``CalendarRecurrence`` exposes one static factory per RFC 5545 frequency:
``minutely()``, ``daily()``, ``weekly()``, ``monthly()``, ``yearly()``, each
accepting the usual ``interval``, ``count`` and ``until`` named arguments. For example::

    // every other day
    CalendarRecurrence::daily(interval: 2);

    // six monthly occurrences
    CalendarRecurrence::monthly(count: 6);

    // yearly until 2030
    CalendarRecurrence::yearly(until: new \DateTimeImmutable('2030-01-01'));

.. note::

    ``CalendarRecurrence::minutely()`` produces a valid RFC 5545 RRULE, but
    Google Calendar and Outlook deeplink URLs do not support ``FREQ=MINUTELY``
    and will silently ignore it. Use ``minutely()`` only when targeting the
    ``ics`` provider.

``CalendarReminder`` has a single ``before()`` factory. Pass any combination
of ``weeks``, ``days``, ``hours``, and ``minutes`` as named arguments, with
an optional ``description``. All units are summed::

    // 15 minutes before
    CalendarReminder::before(minutes: 15);

    // 2 hours before, with a description
    CalendarReminder::before(hours: 2, description: 'Leave early');

    // one day before
    CalendarReminder::before(days: 1);

    // 1 hour and 30 minutes before
    CalendarReminder::before(hours: 1, minutes: 30);

All-day events
--------------

Pass ``allDay: true`` to create an event with no specific start or end time::

    $holiday = new CalendarEvent(
        title: 'Bastille Day',
        start: new \DateTimeImmutable('2026-07-14'),
        end:   new \DateTimeImmutable('2026-07-14'),
        allDay: true,
    );

Backward compatibility promise
------------------------------

This bundle follows the same backward-compatibility promise as
`Symfony itself <https://symfony.com/doc/current/contributing/code/bc.html>`_.
