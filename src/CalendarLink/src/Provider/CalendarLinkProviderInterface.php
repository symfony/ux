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
 * Produces an "Add to calendar" {@see CalendarLink} for a given target (Google Calendar, Outlook, .ics file, ...).
 *
 * Implementations are registered as services tagged `ux_calendar_link.provider` and picked up by the
 * {@see \Symfony\UX\CalendarLink\Registry\CalendarLinkProviderRegistry}.
 *
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 */
interface CalendarLinkProviderInterface
{
    /**
     * Returns the unique machine name identifying this provider (e.g. "google", "outlook", "ics").
     *
     * The name is the key used to select a provider in Twig and in the registry.
     */
    public function getName(): string;

    /**
     * Returns a human-readable label suitable for display in UI (e.g. "Google Calendar").
     */
    public function getLabel(): string;

    /**
     * Builds a {@see CalendarLink} for the given event according to this provider's URL scheme.
     */
    public function generate(CalendarEvent $event): CalendarLink;
}
