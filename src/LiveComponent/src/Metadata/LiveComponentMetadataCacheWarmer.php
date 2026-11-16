<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Metadata;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * @internal
 */
final class LiveComponentMetadataCacheWarmer implements CacheWarmerInterface, ServiceSubscriberInterface
{
    /**
     * @param list<class-string> $liveComponentClasses
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $liveComponentClasses,
    ) {
    }

    public static function getSubscribedServices(): array
    {
        return [
            LiveComponentMetadataFactory::class,
        ];
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->container->get(LiveComponentMetadataFactory::class)->warmUp($this->liveComponentClasses);

        return [];
    }

    public function isOptional(): bool
    {
        return true;
    }
}
