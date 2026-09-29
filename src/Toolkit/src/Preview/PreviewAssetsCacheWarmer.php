<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Preview;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Regenerates the preview files of every previewed kit, so they exist before Tailwind and AssetMapper read them.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PreviewAssetsCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly PreviewKitRegistry $kitRegistry,
        private readonly PreviewAssetsGenerator $generator,
        private readonly Filesystem $filesystem,
        private readonly string $outputDir,
    ) {
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $files = [];
        foreach ($this->kitRegistry->getKits() as $kitName => $kit) {
            array_push($files, ...$this->generator->generate($kitName, $kit, $this->outputDir));
        }

        $previousFiles = array_map(Path::canonicalize(...), glob($this->outputDir.'/*') ?: []);
        $this->filesystem->remove(array_diff($previousFiles, $files));

        return [];
    }
}
