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

use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\Registry\CacheIconRegistry;
use Symfony\UX\Icons\Twig\IconFinder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class IconCacheWarmer
{
    public function __construct(private CacheIconRegistry $registry, private IconFinder $icons)
    {
    }

    /**
     * @param callable(string,Icon):void|null $onSuccess
     * @param callable(string):void|null      $onFailure
     */
    public function warm(?callable $onSuccess = null, ?callable $onFailure = null): void
    {
        $onSuccess ??= fn (string $name, Icon $icon) => null;
        $onFailure ??= fn (string $name) => null;

        foreach ($this->icons->icons() as $name) {
            try {
                $icon = $this->registry->get($name, refresh: true);

                $onSuccess($name, $icon);
            } catch (IconNotFoundException) {
                $onFailure($name);
            }
        }
    }
}
