<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Provider;

use Symfony\UX\Image\Exception\UnsupportedSchemeException;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ProviderResolver
{
    /**
     * @param iterable<ProviderFactoryInterface> $factories
     */
    public function __construct(private readonly iterable $factories)
    {
    }

    public function fromString(#[\SensitiveParameter] string $dsn): ProviderInterface
    {
        $dsn = new Dsn($dsn);

        foreach ($this->factories as $factory) {
            if ($factory->supports($dsn)) {
                return $factory->create($dsn);
            }
        }

        throw new UnsupportedSchemeException($dsn);
    }
}
