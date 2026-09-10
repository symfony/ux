<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Audit;

/**
 * Outcome of a disclosure request, shared by the audit trail and the
 * disclosure events.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
enum DiscloseStatus: string
{
    case Attempt = 'attempt';
    case Success = 'success';
    case AuthDenied = 'auth_denied';
    case RateLimited = 'rate_limited';
}
