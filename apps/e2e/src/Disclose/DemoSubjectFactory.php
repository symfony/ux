<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Disclose;

use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Disclose\RateLimiter\DiscloseRateLimitSubjectFactoryInterface;

/**
 * Keys the disclosure rate limiter on a reference sent as a cookie, so each
 * demo visitor (or each parallel browser project) gets an isolated budget.
 *
 * Demo only: the cookie is user-controlled, so anyone can change it to reset
 * their own budget. Do not reuse this pattern in production.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class DemoSubjectFactory implements DiscloseRateLimitSubjectFactoryInterface
{
    public function create(Request $request): ?string
    {
        return null !== $request->cookies->get('ux_disclose_subject')
            ? 'reference:'.$request->cookies->get('ux_disclose_subject')
            : null;
    }
}
