<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide;

use League\Glide\Signatures\SignatureFactory as LeagueSignatureFactory;
use League\Glide\Signatures\SignatureInterface;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Provider\Dsn;

/**
 * Builds the optional League\Glide\Signatures\Signature from the provider DSN at service-instantiation
 * time, same reason as ServerFactory: an "%env(...)%" DSN placeholder resolves too late for compile time.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class SignatureFactory
{
    public static function createFromDsn(#[\SensitiveParameter] string $dsn, string $environment): ?SignatureInterface
    {
        $signKey = new GlideProviderFactory()->resolveGlideOptions(new Dsn($dsn))['sign_key'] ?? null;

        if (null === $signKey && !\in_array($environment, ['dev', 'test'], true)) {
            throw new InvalidArgumentException(\sprintf('The Glide image provider requires a "sign_key" in the "%s" environment, or anyone can make it resize and cache any image at any size. Only "dev" and "test" may run without one.', $environment));
        }

        return null !== $signKey ? LeagueSignatureFactory::create($signKey) : null;
    }
}
