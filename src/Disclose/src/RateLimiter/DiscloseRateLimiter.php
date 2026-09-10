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

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\CompoundLimiter;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Consumes a token for the current request against one or more rate limiters.
 *
 * Several limiters can be combined (for example a short burst window and a
 * daily quota): a request is accepted only when every limiter accepts it. The
 * limiters run through Symfony's CompoundLimiter, which consumes on each in
 * turn and does not roll back: if a later limiter rejects, the earlier ones
 * have already spent a token. List the most restrictive limiter first to avoid
 * burning tokens that will not buy a disclosure. The key is the authenticated
 * user, falling back to the client IP, unless the application provides a
 * custom subject factory.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class DiscloseRateLimiter
{
    /**
     * @param RateLimiterFactory[] $rateLimiterFactories
     */
    public function __construct(
        private readonly ?Security $security = null,
        private readonly array $rateLimiterFactories = [],
        private readonly ?DiscloseRateLimitSubjectFactoryInterface $subjectFactory = null,
    ) {}

    /**
     * Consumes one token. Returns null when no rate limiter is configured.
     */
    public function consume(Request $request): ?RateLimit
    {
        if (!$this->rateLimiterFactories) {
            return null;
        }

        $key = $this->resolveSubject($request);
        $limiters = [];

        foreach ($this->rateLimiterFactories as $factory) {
            $limiters[] = $factory->create($key);
        }

        if (1 === \count($limiters)) {
            return $limiters[0]->consume();
        }

        return new CompoundLimiter($limiters)->consume();
    }

    /**
     * Returns the identity the current request is accounted against.
     */
    public function identity(Request $request): string
    {
        return $this->resolveSubject($request);
    }

    private function resolveSubject(Request $request): string
    {
        if (null !== $this->subjectFactory && null !== $subject = $this->subjectFactory->create($request)) {
            return $subject;
        }

        $user = $this->security?->getUser();

        if ($user instanceof UserInterface) {
            return 'user:' . $user->getUserIdentifier();
        }

        return 'ip:' . $request->getClientIp();
    }
}
