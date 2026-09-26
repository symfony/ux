<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Command;

use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * The formats a tagged locator provides, keyed by the "format" tag attribute
 * or, without it, by the service id.
 *
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class Formats
{
    /** @param ServiceProviderInterface<mixed> $services */
    public function __construct(
        private readonly ServiceProviderInterface $services,
    ) {
    }

    /** @return list<string> */
    public function names(): array
    {
        $names = array_map(strval(...), array_keys($this->services->getProvidedServices()));
        sort($names);

        return $names;
    }

    /**
     * The registered name a requested format matches, compared case-insensitively.
     */
    public function find(string $requested): ?string
    {
        foreach ($this->names() as $name) {
            if (0 === strcasecmp($name, $requested)) {
                return $name;
            }
        }

        return null;
    }

    public function get(string $name): mixed
    {
        return $this->services->get($name);
    }
}
