<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Fixtures;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Stubs the Security/CSRF Twig functions used by the `common` kit recipes, so the
 * components can be rendered by the functional snapshot tests without registering
 * the whole SecurityBundle (and a firewall) in the test kernel.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SecurityTwigStubExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('logout_path', static fn (?string $key = null): string => '/logout'.(null !== $key ? '?firewall='.$key : '')),
            new TwigFunction('logout_url', static fn (?string $key = null): string => 'http://localhost/logout'.(null !== $key ? '?firewall='.$key : '')),
            new TwigFunction('csrf_token', static fn (string $tokenId): string => 'csrf-token-for-'.$tokenId),
        ];
    }
}
