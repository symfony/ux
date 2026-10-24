<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class DesignTokensCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly StylesheetCache $stylesheets,
    ) {
    }

    public function isOptional(): bool
    {
        return true;
    }

    /**
     * Returns no file: what a warmer returns is appended to the opcache preload script.
     *
     * @return list<string>
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->stylesheets->path();

        return [];
    }
}
