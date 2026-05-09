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
final class Office365CalendarLinkProvider extends AbstractOutlookCalendarLinkProvider
{
    public function getName(): string
    {
        return 'office365';
    }

    public function getLabel(): string
    {
        return 'Office 365';
    }

    protected function getBaseUrl(): string
    {
        return 'https://outlook.office.com/calendar/0/deeplink/compose';
    }
}
