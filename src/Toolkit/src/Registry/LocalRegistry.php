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
use Symfony\Component\Finder\Finder;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitFactory;

/**
 * @internal
 *
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class LocalRegistry implements RegistryInterface
{
    private static string $kitsDir = __DIR__.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'kits';

    public static function supports(string $kitName): bool
    {
        return 1 === preg_match('/^[a-zA-Z0-9_-]+$/', $kitName);
    }

    public function __construct(
        private readonly KitFactory $kitFactory,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function getKit(string $kitName): Kit
    {
        $kitDir = Path::join(self::$kitsDir, $kitName);
        if ($this->filesystem->exists($kitDir)) {
            return $this->kitFactory->createKitFromAbsolutePath($kitDir);
        }

        throw new \RuntimeException(\sprintf('Unable to find the kit "%s" in the following directories: "%s"', $kitName, implode('", "', $possibleKitDirs)));
    }

    /**
     * @return array<string>
     */
    public static function getAvailableKitsName(): array
    {
        $availableKitsName = [];
        $finder = (new Finder())->directories()->in(self::$kitsDir)->depth(0);

        foreach ($finder as $directory) {
            $kitName = $directory->getRelativePathname();
            if (self::supports($kitName)) {
                $availableKitsName[] = $kitName;
            }
        }

        return $availableKitsName;
    }
}
