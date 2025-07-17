<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\CacheWarmer;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\UX\TwigComponent\ComponentProperties;

/**
 * Warm the TwigComponent metadata caches.
 *
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class TwigComponentCacheWarmer implements CacheWarmerInterface, ServiceSubscriberInterface
{
    /**
     * As this cache warmer is optional, dependencies should be lazy-loaded, that's why a container should be injected.
     */
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public static function getSubscribedServices(): array
    {
        return [
            'ux.twig_component.component_properties' => ComponentProperties::class,
        ];
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $properties = $this->container->get('ux.twig_component.component_properties');
        $properties->warmup();

        return [];
    }

    public function isOptional(): bool
    {
        return true;
    }
}
