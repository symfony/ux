<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class KitFactory
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly KitSynchronizer $kitSynchronizer,
    ) {
    }

    /**
     * @throws \InvalidArgumentException if the manifest file is missing a required key
     */
    public function createKitFromAbsolutePath(string $absolutePath): Kit
    {
        if (!Path::isAbsolute($absolutePath)) {
            throw new \InvalidArgumentException(\sprintf('Path "%s" is not absolute.', $absolutePath));
        }

        if (!$this->filesystem->exists($absolutePath)) {
            throw new \InvalidArgumentException(\sprintf('Path "%s" does not exist.', $absolutePath));
        }

        if (!$this->filesystem->exists($manifestPath = Path::join($absolutePath, 'manifest.json'))) {
            throw new \InvalidArgumentException(\sprintf('File "%s" not found.', $manifestPath));
        }

        try {
            $manifest = KitManifest::fromJson(file_get_contents($manifestPath));
        } catch (\JsonException $e) {
            throw new \RuntimeException(\sprintf('Unable to parse "%s"', $manifestPath), previous: $e);
        }

        $kit = new Kit(absolutePath: $absolutePath, manifest: $manifest);

        $this->kitSynchronizer->synchronize($kit);

        return $kit;
    }
}
