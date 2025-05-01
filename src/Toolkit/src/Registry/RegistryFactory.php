<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Registry;

use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class RegistryFactory
{
    public function __construct(
        private readonly ContainerInterface $registries,
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getForKit(string $kit): Registry
    {
        $type = match (true) {
            GitHubRegistry::supports($kit) => Type::GitHub,
            LocalRegistry::supports($kit) => Type::Local,
            default => throw new \InvalidArgumentException(\sprintf('The kit "%s" is not valid.', $kit)),
        };

        if (!$this->registries->has($type->value)) {
            throw new \LogicException(\sprintf('The registry for the kit "%s" is not registered.', $kit));
        }

        return $this->registries->get($type->value);
    }
}
