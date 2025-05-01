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
     * @throws \JsonException            if the manifest file is not valid JSON
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

        $manifest = json_decode($this->filesystem->readFile($manifestPath), true, flags: \JSON_THROW_ON_ERROR);

        $kit = new Kit(
            path: $absolutePath,
            name: $manifest['name'] ?? throw new \InvalidArgumentException('Manifest file is missing "name" key.'),
            homepage: $manifest['homepage'] ?? throw new \InvalidArgumentException('Manifest file is missing "homepage" key.'),
            authors: $manifest['authors'] ?? throw new \InvalidArgumentException('Manifest file is missing "authors" key.'),
            license: $manifest['license'] ?? throw new \InvalidArgumentException('Manifest file is missing "license" key.'),
            description: $manifest['description'] ?? null,
            uxIcon: $manifest['ux-icon'] ?? null,
        );

        $this->kitSynchronizer->synchronize($kit);

        return $kit;
    }
}
