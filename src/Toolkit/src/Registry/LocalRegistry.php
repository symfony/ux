<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Registry;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitFactory;

/**
 * @internal
 *
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final readonly class LocalRegistry implements Registry
{
    public static function supports(string $kitName): bool
    {
        return 1 === preg_match('/^[a-zA-Z0-9_-]+$/', $kitName);
    }

    public function __construct(
        private KitFactory $kitFactory,
        private Filesystem $filesystem,
        private string $projectDir,
    ) {
    }

    public function getKit(string $kitName): Kit
    {
        $possibleKitDirs = [
            // Local kit
            Path::join($this->projectDir, 'kits', $kitName),
            // From vendor
            Path::join($this->projectDir, 'vendor', 'symfony', 'ux-toolkit', 'kits', $kitName),
        ];

        foreach ($possibleKitDirs as $kitDir) {
            if ($this->filesystem->exists($kitDir)) {
                return $this->kitFactory->createKitFromAbsolutePath($kitDir);
            }
        }

        throw new \RuntimeException(\sprintf('Unable to find the kit "%s" in the following directories: "%s"', $kitName, implode('", "', $possibleKitDirs)));
    }
}
