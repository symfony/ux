<?php

namespace App\Disclose;

use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Disclose\RateLimiter\DiscloseRateLimitSubjectFactoryInterface;

/**
 * Keys the disclosure rate limit on a per-visitor reference sent as a cookie,
 * so every browser gets an isolated disclosure budget.
 *
 * Demo only: the cookie is user-controlled, so a visitor can change it to
 * reset their own budget. Do not reuse this pattern in production.
 */
final class DemoSubjectFactory implements DiscloseRateLimitSubjectFactoryInterface
{
    public function create(Request $request): ?string
    {
        return null !== $request->cookies->get('ux_disclose_subject')
            ? 'reference:' . $request->cookies->get('ux_disclose_subject')
            : null;
    }
}
