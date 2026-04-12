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

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 */
final class UXCalendarLinkExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ux_calendar_link', [UXCalendarLinkRuntime::class, 'link']),
            new TwigFunction('ux_calendar_links', [UXCalendarLinkRuntime::class, 'links']),
        ];
    }
}
