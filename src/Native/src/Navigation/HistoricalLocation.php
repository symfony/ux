<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Navigation;

/**
 * The navigation actions that Hotwire Native apps (1.2 or later) perform instead of displaying a page.
 */
enum HistoricalLocation: string
{
    /**
     * Dismisses the modal if there is one, otherwise goes back to the previous screen.
     */
    case Recede = 'recede';

    /**
     * Dismisses the modal if there is one, then reloads the visible screen.
     */
    case Refresh = 'refresh';

    /**
     * Dismisses the modal if there is one, and does nothing else.
     */
    case Resume = 'resume';
}
