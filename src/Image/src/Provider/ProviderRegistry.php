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

/**
 * Registry that holds and provides access to image providers.
 *
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
class ProviderRegistry
{
    /**
     * @var array<string, ProviderInterface>
     */
    private array $providers = [];
    private ?string $defaultProvider = null;

    public function addProvider(ProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    public function setDefaultProvider(string $name): void
    {
        $this->defaultProvider = $name;
    }

    public function getProvider(?string $name = null): ProviderInterface
    {
        $name ??= $this->defaultProvider;

        if (null === $name) {
            throw new \InvalidArgumentException('No default provider configured and no provider name specified.');
        }

        if (!isset($this->providers[$name])) {
            throw new \InvalidArgumentException(\sprintf('Provider "%s" not found. Available providers: %s', $name, implode(', ', array_keys($this->providers))));
        }

        return $this->providers[$name];
    }

    /**
     * @return string[]
     */
    public function getProviderNames(): array
    {
        return array_keys($this->providers);
    }
}
