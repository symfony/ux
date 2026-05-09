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

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 */
final class OutlookCalendarLinkProvider extends AbstractOutlookCalendarLinkProvider
{
    public function getName(): string
    {
        return 'outlook';
    }

    public function getLabel(): string
    {
        return 'Outlook.com';
    }

    protected function getBaseUrl(): string
    {
        return 'https://outlook.live.com/calendar/0/deeplink/compose';
    }
}
