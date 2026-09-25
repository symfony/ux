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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class IconCacheWarmer implements CacheWarmerInterface
{
    public function __construct(private CacheIconRegistry $registry, private IconFinderInterface $icons)
    {
    }

    /**
     * @param callable(string,Icon):void|null       $onSuccess
     * @param callable(string,\Exception):void|null $onFailure
     */
    public function warm(?callable $onSuccess = null, ?callable $onFailure = null): void
    {
        $onSuccess ??= static fn (string $name, Icon $icon) => null;
        $onFailure ??= static fn (string $name) => null;

        foreach ($this->icons->icons() as $name) {
            try {
                $icon = $this->registry->get($name, refresh: true);

                $onSuccess($name, $icon);
            } catch (IconNotFoundException $e) {
                $onFailure($name, $e);
            }
        }
    }

    public function isOptional(): bool
    {
        return true;
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
