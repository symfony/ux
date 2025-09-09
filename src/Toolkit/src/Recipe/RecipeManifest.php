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
use Symfony\UX\Toolkit\Dependency\DependencyInterface;
use Symfony\UX\Toolkit\Dependency\ImportmapPackageDependency;
use Symfony\UX\Toolkit\Dependency\NpmPackageDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\RecipeDependency;
use Symfony\UX\Toolkit\Dependency\Version;

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
            throw new \InvalidArgumentException(\sprintf('The recipe type "%s" is not supported.', $data['type']));
        }

        $dependencies = [];
        foreach ($data['dependencies'] ?? [] as $i => $dependency) {
            if (!\is_array($dependency)) {
                throw new \InvalidArgumentException('Each dependency must be an associative array.');
            }
            if (!isset($dependency['type'])) {
                throw new \InvalidArgumentException(\sprintf('The dependency #%d is missing "type" field.', $i));
            }

            if ('php' === $dependency['type']) {
                $name = $dependency['name'] ?? throw new \InvalidArgumentException(\sprintf('The dependency #%d of type "php" is missing "name" field.', $i));
                $version = $dependency['version'] ?? null;
                $dependencies[] = new PhpPackageDependency($name, null === $version ? null : new Version($version));
            } elseif ('recipe' === $dependency['type']) {
                $name = $dependency['name'] ?? throw new \InvalidArgumentException(\sprintf('The dependency #%d of type "recipe" is missing "name" field.', $i));
                $dependencies[] = new RecipeDependency($name);
            } elseif ('npm' === $dependency['type']) {
                $name = $dependency['name'] ?? throw new \InvalidArgumentException(\sprintf('The dependency #%d of type "npm" is missing "name" field.', $i));
                $version = $dependency['version'] ?? null;
                $dependencies[] = new NpmPackageDependency($name, null === $version ? null : new Version($version));
            } elseif ('importmap' === $dependency['type']) {
                $package = $dependency['package'] ?? throw new \InvalidArgumentException(\sprintf('The dependency #%d of type "importmap" is missing "package" field.', $i));
                $dependencies[] = new ImportmapPackageDependency($package);
            } else {
                throw new \InvalidArgumentException(\sprintf('The dependency type "%s" is not supported.', $dependency['type']));
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
