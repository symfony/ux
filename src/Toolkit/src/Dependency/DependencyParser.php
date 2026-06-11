<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Dependency;

/**
 * Parses the `dependencies` object of a Kit or Recipe manifest into typed dependencies.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class DependencyParser
{
    /**
     * @param array<string, mixed>|null $dependencies The raw `dependencies` object, or null when absent
     * @param bool                      $allowRecipe  Whether the `recipe` dependency type is allowed (Recipes only)
     *
     * @return list<DependencyInterface>
     *
     * @throws \InvalidArgumentException
     */
    public static function parse(?array $dependencies, bool $allowRecipe): array
    {
        if (null === $dependencies) {
            return [];
        }

        if ([] !== $dependencies && array_values($dependencies) === $dependencies) {
            throw new \InvalidArgumentException('The "dependencies" property must be an object.');
        }

        $parsed = [];

        if ($allowRecipe) {
            foreach ($dependencies['recipe'] ?? [] as $i => $name) {
                if (!\is_string($name) || '' === $name) {
                    throw new \InvalidArgumentException(\sprintf('The dependency #%d of type "recipe" must be a non-empty string.', $i));
                }

                $parsed[] = new RecipeDependency($name);
            }
            unset($dependencies['recipe']);
        }

        foreach ($dependencies['composer'] ?? [] as $i => $package) {
            if (!\is_string($package) || '' === $package) {
                throw new \InvalidArgumentException(\sprintf('The dependency #%d of type "composer" must be a non-empty string.', $i));
            }

            // format: "package:version"
            if (str_contains($package, ':')) {
                [$name, $version] = explode(':', $package, 2);
                $parsed[] = new PhpPackageDependency($name, new ConstraintVersion($version));
            } else {
                $parsed[] = new PhpPackageDependency($package);
            }
        }

        foreach ($dependencies['npm'] ?? [] as $i => $package) {
            if (!\is_string($package) || '' === $package) {
                throw new \InvalidArgumentException(\sprintf('The dependency #%d of type "npm" must be a non-empty string.', $i));
            }

            // format: "package@version", "@scope/package", "@scope/package@version"
            $name = $package;
            $version = null;
            $versionPos = strrpos($package, '@');
            if (false !== $versionPos && 0 !== $versionPos) {
                $name = substr($package, 0, $versionPos);
                $version = substr($package, $versionPos + 1);
            }

            if (null !== $version) {
                $parsed[] = new NpmPackageDependency($name, new ConstraintVersion($version));
            } else {
                $parsed[] = new NpmPackageDependency($name);
            }
        }

        foreach ($dependencies['importmap'] ?? [] as $i => $package) {
            if (!\is_string($package) || '' === $package) {
                throw new \InvalidArgumentException(\sprintf('The dependency #%d of type "importmap" must be a non-empty string.', $i));
            }

            $parsed[] = new ImportmapPackageDependency($package);
        }

        unset($dependencies['composer'], $dependencies['npm'], $dependencies['importmap']);

        if ([] !== $dependencies) {
            throw new \InvalidArgumentException(\sprintf('The dependency types "%s" are not supported.', implode('", "', array_keys($dependencies))));
        }

        return $parsed;
    }
}
