<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\CacheWarmer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Creates the dump directory when the container is built.
 *
 * The dump directory is an AssetMapper path, which must exist as soon as the container is built.
 * It stays empty until `cache:warmup`, `ux:router:warm-cache` or, in debug mode, the next request dumps the routes.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class DumpDirectoryCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private Filesystem $filesystem,
        private string $dumpDir,
    ) {
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->filesystem->mkdir($this->dumpDir);

        return [];
    }
}
