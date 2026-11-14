<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\Registry\CacheIconRegistry;
use Symfony\UX\Icons\Registry\IconifyOnDemandRegistry;
use Symfony\UX\Icons\Registry\LocalSvgIconRegistry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class IconCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private CacheIconRegistry $registry,
        private IconFinderInterface $icons,
        private ?IconifyOnDemandRegistry $iconifyOnDemand = null,
        private ?LocalSvgIconRegistry $localIcons = null,
    ) {
    }

    /**
     * @param callable(string,Icon):void|null       $onSuccess
     * @param callable(string,\Exception):void|null $onFailure
     */
    public function warm(?callable $onSuccess = null, ?callable $onFailure = null): void
    {
        $onSuccess ??= static fn (string $name, Icon $icon) => null;
        $onFailure ??= static fn (string $name) => null;

        if (!$this->iconifyOnDemand) {
            $this->warmAll($this->icons->icons(), $onSuccess, $onFailure);

            return;
        }

        // all the names are needed before the first fetch, to start their requests together
        $names = [];
        $finderError = null;
        try {
            foreach ($this->icons->icons() as $name) {
                $names[] = $name;
            }
        } catch (\Throwable $e) {
            $finderError = $e;
        }

        $this->iconifyOnDemand->prefetch($this->remoteNames($names));
        $this->warmAll($names, $onSuccess, $onFailure);

        if ($finderError) {
            throw $finderError;
        }
    }

    public function isOptional(): bool
    {
        return true;
    }

    /**
     * @param iterable<string>                 $names
     * @param callable(string,Icon):void       $onSuccess
     * @param callable(string,\Exception):void $onFailure
     */
    private function warmAll(iterable $names, callable $onSuccess, callable $onFailure): void
    {
        foreach ($names as $name) {
            try {
                $icon = $this->registry->get($name, refresh: true);

                $onSuccess($name, $icon);
            } catch (IconNotFoundException $e) {
                $onFailure($name, $e);
            }
        }
    }

    /**
     * @param array<string> $names
     *
     * @return list<string>
     */
    private function remoteNames(array $names): array
    {
        if (!$this->localIcons) {
            return array_values($names);
        }

        $remote = [];
        foreach ($names as $name) {
            if (!$this->localIcons->has($name)) {
                $remote[] = $name;
            }
        }

        return $remote;
    }

    /**
     * Warms the icons that are not cached yet, and never fails.
     *
     * An icon that cannot be warmed is left to its first rendering, which reports the error.
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        try {
            foreach ($this->icons->icons() as $name) {
                try {
                    $this->registry->get($name);
                } catch (\Exception) {
                }
            }
        } catch (\Exception) {
        }

        return [];
    }
}
