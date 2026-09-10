<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\RateLimiter;

use Symfony\Component\HttpFoundation\Request;

/**
 * Computes the subject the rate limiter is keyed on, per request.
 *
 * By default the subject is the authenticated user, or the client IP for
 * anonymous requests. Applications can provide their own factory to key the
 * limiter differently (for example per session, per tenant, or per reference).
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
interface DiscloseRateLimitSubjectFactoryInterface
{
    /**
     * Returns the rate-limit subject, or null to fall back to the default
     * user-then-IP resolution.
     */
    public function create(Request $request): ?string;
}
