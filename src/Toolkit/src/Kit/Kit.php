<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit;

use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeType;

/**
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class Kit
{
    /**
     * @var array<string,Recipe>
     */
    private array $recipes = [];

    /**
     * @param non-empty-string $absolutePath
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public readonly string $absolutePath,
        public readonly KitManifest $manifest,
        public ?string $installAsMarkdown = null,
    ) {
        if (!Path::isAbsolute($this->absolutePath)) {
            throw new \InvalidArgumentException(\sprintf('Kit path "%s" is not absolute.', $this->absolutePath));
        }
    }

    public function addRecipe(Recipe $recipe): void
    {
        if (\array_key_exists($recipe->name, $this->recipes)) {
            throw new \InvalidArgumentException(\sprintf('Recipe "%s" is already registered in the kit.', $recipe->manifest->name));
        }

        $this->recipes[$recipe->name] = $recipe;
    }

    /**
     * @return array<Recipe>
     */
    public function getRecipes(?RecipeType $type = null): array
    {
        if (null !== $type) {
            return array_filter($this->recipes, static fn (Recipe $recipe) => $recipe->manifest->type === $type);
        }

        return $this->recipes;
    }

    public function getRecipe(string $name, ?RecipeType $type = null): ?Recipe
    {
        if (null === $recipe = $this->recipes[$name] ?? null) {
            return null;
        }

        if (null !== $type && $recipe->manifest->type !== $type) {
            return null;
        }

        return $recipe;
    }
}
