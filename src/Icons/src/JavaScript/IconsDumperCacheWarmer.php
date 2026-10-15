<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\JavaScript;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * @internal
 */
final class IconsDumperCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly IconsDumper $dumper,
    ) {
    }

    /**
     * The dump directory is an AssetMapper path, which must exist as soon as the container is built.
     *
     * Dumping only reads local icons, it never downloads anything.
     */
    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->dumper->dump();

        return [];
    }
}
