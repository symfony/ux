<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\UX\Css\Dumper\StylesheetDumper;

/**
 * Writes the stylesheet of every template when the cache is built.
 *
 * Not optional, so that a container built on the first request of a production server writes it too.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class StylesheetCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly StylesheetDumper $dumper,
    ) {
    }

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
