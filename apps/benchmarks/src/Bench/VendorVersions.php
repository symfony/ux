<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Bench;

final class VendorVersions
{
    private function __construct(
        public readonly string $symfony,
        public readonly string $twig,
        public readonly string $tailwindExtra,
    ) {
    }

    public static function of(string $symfony, string $twig, string $tailwindExtra): self
    {
        return new self($symfony, $twig, $tailwindExtra);
    }

    public static function read(string $projectDir): self
    {
        $file = \sprintf('%s/vendor/composer/installed.php', $projectDir);
        $installed = is_file($file) ? require $file : [];

        return new self(
            self::version($installed, 'symfony/framework-bundle'),
            self::version($installed, 'twig/twig'),
            self::version($installed, 'tales-from-a-dev/twig-tailwind-extra'),
        );
    }

    /**
     * @param array<string, mixed> $installed
     */
    private static function version(array $installed, string $package): string
    {
        return ltrim($installed['versions'][$package]['pretty_version'] ?? '', 'v');
    }
}
