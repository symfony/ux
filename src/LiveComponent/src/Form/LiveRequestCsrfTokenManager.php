<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Form;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\LiveComponent\EventListener\LiveComponentSubscriber;

/**
 * Accepts the stateless form CSRF token during a live component request.
 *
 * The live component endpoints are already protected against CSRF by requiring the X-Requested-With header, while the stateless CSRF protection cannot be met there: live requests never double-submit a token.
 * Session tokens are still validated by the decorated manager, as is every token outside a live request.
 *
 * @internal
 */
final class LiveRequestCsrfTokenManager implements CsrfTokenManagerInterface
{
    public function __construct(
        private CsrfTokenManagerInterface $decorated,
        private RequestStack $requestStack,
    ) {
    }

    public function getToken(string $tokenId): CsrfToken
    {
        return $this->decorated->getToken($tokenId);
    }

    public function refreshToken(string $tokenId): CsrfToken
    {
        return $this->decorated->refreshToken($tokenId);
    }

    public function removeToken(string $tokenId): ?string
    {
        return $this->decorated->removeToken($tokenId);
    }

    public function isTokenValid(CsrfToken $token): bool
    {
        if (!$this->requestStack->getMainRequest()?->attributes->get(LiveComponentSubscriber::LIVE_REQUEST_ATTRIBUTE)) {
            return $this->decorated->isTokenValid($token);
        }

        // getToken() returns a fixed value for a stateless token, and a value randomized on each call for a session token
        $renderedToken = $this->decorated->getToken($token->getId())->getValue();

        return hash_equals($renderedToken, $token->getValue()) || $this->decorated->isTokenValid($token);
    }
}
