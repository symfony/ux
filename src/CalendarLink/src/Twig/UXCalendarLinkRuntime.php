<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\CalendarLink\Twig;

use Symfony\UX\CalendarLink\CalendarEvent;
use Symfony\UX\CalendarLink\CalendarLink;
use Symfony\UX\CalendarLink\Registry\CalendarLinkProviderRegistry;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 */
final class UXCalendarLinkRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly CalendarLinkProviderRegistry $registry,
    ) {
    }

    public function link(CalendarEvent $event, string $provider): CalendarLink
    {
        return $this->registry->generate($event, $provider);
    }

    /**
     * @return array<string, CalendarLink>
     */
    public function links(CalendarEvent $event): array
    {
        return $this->registry->generateAll($event);
    }
}
