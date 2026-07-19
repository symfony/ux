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
use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\Dependency\DependencyInterface;
use Symfony\UX\Toolkit\Dependency\DependencyParser;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class RecipeManifest
{
    /**
     * @param non-empty-string                          $name
     * @param array<non-empty-string, non-empty-string> $copyFiles
     * @param list<DependencyInterface>                 $dependencies
     * @param ?non-empty-string                         $versionAdded
     */
    public function __construct(
        public readonly RecipeType $type,
        public readonly string $name,
        public readonly array $copyFiles,
        public readonly array $dependencies = [],
        public readonly ?string $versionAdded = null,
    ) {
        foreach ($this->copyFiles as $source => $destination) {
            if (!Path::isRelative($source)) {
                throw new \InvalidArgumentException(\sprintf('Copy file source "%s" must be a relative path.', $source));
            }
            if (!Path::isRelative($destination)) {
                throw new \InvalidArgumentException(\sprintf('Copy file destination "%s" must be a relative path.', $destination));
            }

            Assert::pathDoesNotEscapeDirectory($source);
            Assert::pathDoesNotEscapeDirectory($destination);
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

        $dependencies = DependencyParser::parse($data['dependencies'] ?? null, allowRecipe: true);

        $versionAdded = $data['version-added'] ?? null;
        if (null !== $versionAdded && (!\is_string($versionAdded) || '' === $versionAdded)) {
            throw new \InvalidArgumentException('The "version-added" property must be a non-empty string.');
        }

        return new self(
            type: $type,
            name: $data['name'] ?? throw new \InvalidArgumentException('Property "name" is required.'),
            copyFiles: $data['copy-files'] ?? [],
            dependencies: $dependencies,
            versionAdded: $versionAdded,
        );
    }
}
