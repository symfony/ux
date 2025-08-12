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

        $dependencies = [];
        foreach ($data['dependencies'] ?? [] as $i => $dependency) {
            if (!\is_array($dependency)) {
                throw new \InvalidArgumentException('Each dependency must be an associative array.');
            }
            if (!isset($dependency['type'])) {
                throw new \InvalidArgumentException(\sprintf('The dependency type is missing for dependency #%d, add "type" key.', $i));
            }

            if ('php' === $dependency['type']) {
                $package = $dependency['package'] ?? throw new \InvalidArgumentException(\sprintf('The package name is missing for dependency #%d, add "package" key.', $i));
                if (str_contains($package, ':')) {
                    [$name, $version] = explode(':', $package, 2);
                    $dependencies[] = new PhpPackageDependency($name, new Version($version));
                } else {
                    $dependencies[] = new PhpPackageDependency($package);
                }
            } elseif ('recipe' === $dependency['type']) {
                $name = $dependency['name'] ?? throw new \InvalidArgumentException(\sprintf('The recipe name is missing for dependency #%d, add "name" key.', $i));
                $dependencies[] = new RecipeDependency($name);
            } else {
                throw new \InvalidArgumentException(\sprintf('The dependency type "%s" is not supported.', $dependency['type']));
            }
        }

        $type = $data['type'] ?? throw new \InvalidArgumentException('Property "type" is required.');
        if (null === $type = RecipeType::tryFrom($type)) {
            throw new \InvalidArgumentException(\sprintf('The recipe type "%s" is not supported.', $data['type']));
        }

        return new self(
            type: $type,
            name: $data['name'] ?? throw new \InvalidArgumentException('Property "name" is required.'),
            description: $data['description'] ?? throw new \InvalidArgumentException('Property "description" is required.'),
            copyFiles: $data['copy-files'] ?? throw new \InvalidArgumentException('Property "copy-files" is required.'),
            dependencies: $dependencies,
        );
    }
}
