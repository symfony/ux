<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Form\Extension;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Generates the tokens with the decorated manager, but accepts any token.
 *
 * @internal
 */
final class LiveRequestCsrfTokenManager implements CsrfTokenManagerInterface
{
    public function __construct(
        private CsrfTokenManagerInterface $decorated,
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
        return true;
    }
}
