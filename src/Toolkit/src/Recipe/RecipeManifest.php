<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Recipe;

use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Dependency\ConstraintVersion;
use Symfony\UX\Toolkit\Dependency\DependencyInterface;
use Symfony\UX\Toolkit\Dependency\ImportmapPackageDependency;
use Symfony\UX\Toolkit\Dependency\NpmPackageDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\RecipeDependency;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class RecipeManifest
{
    /**
     * @param non-empty-string                          $name
     * @param non-empty-string                          $description
     * @param array<non-empty-string, non-empty-string> $copyFiles
     * @param list<DependencyInterface>                 $dependencies
     */
    public function __construct(
        public readonly RecipeType $type,
        public readonly string $name,
        public readonly string $description,
        public readonly array $copyFiles,
        public readonly array $dependencies = [],
    ) {
        foreach ($this->copyFiles as $source => $destination) {
            if (!Path::isRelative($source)) {
                throw new \InvalidArgumentException(\sprintf('Copy file source "%s" must be a relative path.', $source));
            }
            if (!Path::isRelative($destination)) {
                throw new \InvalidArgumentException(\sprintf('Copy file destination "%s" must be a relative path.', $destination));
            }
        }
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);

        $type = $data['type'] ?? throw new \InvalidArgumentException('Property "type" is required.');
        if (null === $type = RecipeType::tryFrom($type)) {
            throw new \InvalidArgumentException(\sprintf('The recipe type "%s" is not supported, valid types are "%s".', $data['type'], implode('", "', array_map(static fn (RecipeType $type) => $type->value, RecipeType::cases()))));
        }

        $dependencies = [];
        if (isset($data['dependencies'])) {
            if (!\is_array($data['dependencies']) || array_values($data['dependencies']) === $data['dependencies']) {
                throw new \InvalidArgumentException('The "dependencies" property must be an object.');
            }

            foreach ($data['dependencies']['recipe'] ?? [] as $i => $name) {
                if (!\is_string($name) || '' === $name) {
                    throw new \InvalidArgumentException(\sprintf('The dependency #%d of type "recipe" must be a non-empty string.', $i));
                }

                $dependencies[] = new RecipeDependency($name);
            }
            foreach ($data['dependencies']['composer'] ?? [] as $i => $package) {
                if (!\is_string($package) || '' === $package) {
                    throw new \InvalidArgumentException(\sprintf('The dependency #%d of type "composer" must be a non-empty string.', $i));
                }

                // format: "package:version"
                if (str_contains($package, ':')) {
                    [$name, $version] = explode(':', $package, 2);
                    $dependencies[] = new PhpPackageDependency($name, new ConstraintVersion($version));
                } else {
                    $dependencies[] = new PhpPackageDependency($package);
                }
            }

            foreach ($data['dependencies']['npm'] ?? [] as $i => $package) {
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
                    $dependencies[] = new NpmPackageDependency($name, new ConstraintVersion($version));
                } else {
                    $dependencies[] = new NpmPackageDependency($name);
                }
            }

            foreach ($data['dependencies']['importmap'] ?? [] as $i => $package) {
                if (!\is_string($package) || '' === $package) {
                    throw new \InvalidArgumentException(\sprintf('The dependency #%d of type "importmap" must be a non-empty string.', $i));
                }

                $dependencies[] = new ImportmapPackageDependency($package);
            }

            unset($data['dependencies']['recipe'], $data['dependencies']['composer'], $data['dependencies']['npm'], $data['dependencies']['importmap']);

            if ([] !== $data['dependencies'] ?? []) {
                throw new \InvalidArgumentException(\sprintf('The dependency types "%s" are not supported.', implode('", "', array_keys($data['dependencies']))));
            }
        }

        return new self(
            type: $type,
            name: $data['name'] ?? throw new \InvalidArgumentException('Property "name" is required.'),
            description: $data['description'] ?? throw new \InvalidArgumentException('Property "description" is required.'),
            copyFiles: $data['copy-files'] ?? [],
            dependencies: $dependencies,
        );
    }
}
