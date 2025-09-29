<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests;

use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitManifest;

trait TestHelperTrait
{
    private static function getLocalKitPath(string $kitName): string
    {
        return Path::join(__DIR__, '../kits', $kitName);
    }

    private static function createLocalKit(string $kitName): Kit
    {
        $kitPath = Path::join(__DIR__, '../kits', $kitName);

        return new Kit($kitPath, KitManifest::fromJson(file_get_contents(Path::join($kitPath, 'manifest.json'))));
    }

    private static function getFixtureKitPath(string $kitName): string
    {
        return Path::join(__DIR__, 'Fixtures/kits', $kitName);
    }

    private static function createFixtureKit(string $kitName): Kit
    {
        $kitPath = self::getFixtureKitPath($kitName);

        return new Kit($kitPath, KitManifest::fromJson(file_get_contents(Path::join($kitPath, 'manifest.json'))));
    }
}
