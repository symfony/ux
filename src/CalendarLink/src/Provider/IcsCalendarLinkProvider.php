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
use Symfony\UX\CalendarLink\Ics\IcsBuilder;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 */
final class IcsCalendarLinkProvider implements CalendarLinkProviderInterface
{
    public function __construct(
        private readonly IcsBuilder $icsBuilder,
    ) {
    }

    public function getName(): string
    {
        return 'ics';
    }

    public function getLabel(): string
    {
        return 'iCalendar (.ics)';
    }

    public function generate(CalendarEvent $event): CalendarLink
    {
        $ics = $this->icsBuilder->build($event);

        return new CalendarLink(
            $this->getName(),
            $this->getLabel(),
            'data:text/calendar;charset=utf-8;base64,'.base64_encode($ics),
        );
    }
}
