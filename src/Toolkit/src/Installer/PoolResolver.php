<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Installer;

use Symfony\UX\Toolkit\Dependency\ImportmapPackageDependency;
use Symfony\UX\Toolkit\Dependency\NpmPackageDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\RecipeDependency;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Recipe\Recipe;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PoolResolver
{
    public function resolveForRecipe(Kit $kit, Recipe $recipe): Pool
    {
        $pool = new Pool();

        // Process the component and its dependencies
        $recipesStack = [$recipe];
        $visitedRecipes = new \SplObjectStorage();

        while (!empty($recipesStack)) {
            $currentRecipe = array_pop($recipesStack);

            // Skip circular references
            if ($visitedRecipes->offsetExists($currentRecipe)) {
                continue;
            }

            $visitedRecipes[$currentRecipe] = null;

            foreach ($currentRecipe->getFiles() as $file) {
                $pool->addFile($currentRecipe, $file);
            }

            foreach ($currentRecipe->manifest->dependencies as $dependency) {
                if ($dependency instanceof PhpPackageDependency) {
                    $pool->addPhpPackageDependency($dependency);
                } elseif ($dependency instanceof NpmPackageDependency) {
                    $pool->addNpmPackageDependency($dependency);
                } elseif ($dependency instanceof ImportmapPackageDependency) {
                    $pool->addImportmapPackageDependency($dependency);
                } elseif ($dependency instanceof RecipeDependency) {
                    if (null === $recipeDependency = $kit->getRecipe($dependency->name)) {
                        throw new \LogicException(\sprintf('The recipe "%s" has a dependency on unregistered recipe "%s".', $currentRecipe->name, $dependency->name));
                    }

                    $recipesStack[] = $recipeDependency;
                } else {
                    throw new \RuntimeException(\sprintf('Unknown dependency type: "%s"', $dependency::class));
                }
            }
        }

        return $pool;
    }
}
