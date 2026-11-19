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

final class ComposerJsonRewriter
{
    public static function versionForRef(string $ref): string
    {
        if (1 === preg_match('/^v?(\d+\.\d+\.\d+)$/', $ref, $matches)) {
            return $matches[1];
        }

        return '3.x-dev';
    }

    /**
     * @param array<string, mixed>  $composerJson
     * @param array<string, string> $packagePaths package name => absolute path
     *
     * @return array<string, mixed>
     */
    public static function rewrite(array $composerJson, array $packagePaths, string $version): array
    {
        $repositories = $composerJson['repositories'] ?? [];

        foreach (array_keys($composerJson['require'] ?? []) as $name) {
            if (1 !== preg_match('/^symfony\/(ux-|stimulus-bundle$)/', $name)) {
                continue;
            }

            if (!isset($packagePaths[$name])) {
                unset($composerJson['require'][$name]);

                continue;
            }

            $composerJson['require'][$name] = '@dev';
            $repositories[] = [
                'type' => 'path',
                'url' => $packagePaths[$name],
                'options' => ['symlink' => false, 'versions' => [$name => $version]],
            ];
        }

        unset($composerJson['require-dev'], $composerJson['autoload-dev']);

        if ([] !== $repositories) {
            $composerJson['repositories'] = $repositories;
        }

        return $composerJson;
    }
}
