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
     * @var list<Recipe>
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
        foreach ($this->recipes as $existingRecipe) {
            if ($existingRecipe->manifest->name === $recipe->manifest->name) {
                throw new \InvalidArgumentException(\sprintf('Recipe "%s" is already registered in the kit.', $recipe->manifest->name));
            }
        }

        $this->recipes[] = $recipe;
    }

    /**
     * @return array<Recipe>
     */
    public function getRecipes(?RecipeType $type = null): array
    {
        if (null !== $type) {
            $this->recipes = array_filter($this->recipes, fn (Recipe $recipe) => $recipe->manifest->type === $type);
        }

        return $this->recipes;
    }

    public function getRecipe(string $name, ?RecipeType $type = null): ?Recipe
    {
        foreach ($this->recipes as $recipe) {
            if ($recipe->manifest->name === $name && (null === $type || $recipe->manifest->type === $type)) {
                return $recipe;
            }
        }

        return null;
    }
}
