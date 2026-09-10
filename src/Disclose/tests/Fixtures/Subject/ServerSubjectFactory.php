<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Tests\Fixtures\Subject;

use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Disclose\RateLimiter\DiscloseRateLimitSubjectFactoryInterface;

/**
 * Keys the rate limiter on a subject passed as a server parameter, so each
 * test isolates its own budget.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class ServerSubjectFactory implements DiscloseRateLimitSubjectFactoryInterface
{
    public function create(Request $request): ?string
    {
        return $request->server->get('UX_DISCLOSE_SUBJECT');
    }
}
