<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\CalendarLink\Registry;

use Symfony\UX\CalendarLink\CalendarEvent;
use Symfony\UX\CalendarLink\CalendarLink;
use Symfony\UX\CalendarLink\Exception\UnknownProviderException;
use Symfony\UX\CalendarLink\Provider\CalendarLinkProviderInterface;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 */
final class CalendarLinkProviderRegistry
{
    /** @var array<string, CalendarLinkProviderInterface> */
    private array $providers = [];

    /**
     * @param iterable<CalendarLinkProviderInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->getName()] = $provider;
        }
    }

    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    public function get(string $name): CalendarLinkProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new UnknownProviderException($name, array_keys($this->providers));
        }

        return $this->providers[$name];
    }

    /**
     * @return array<string, CalendarLinkProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }

    public function generate(CalendarEvent $event, string $provider): CalendarLink
    {
        return $this->get($provider)->generate($event);
    }

    /**
     * @return array<string, CalendarLink>
     */
    public function generateAll(CalendarEvent $event): array
    {
        $links = [];
        foreach ($this->providers as $name => $provider) {
            $links[$name] = $provider->generate($event);
        }

        return $links;
    }
}
