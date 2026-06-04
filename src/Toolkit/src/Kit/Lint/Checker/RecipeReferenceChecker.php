<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit\Lint\Checker;

use Symfony\UX\Toolkit\Dependency\RecipeDependency;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;

/**
 * Verifies every `dependencies.recipe[]` entry points to a recipe that exists in the same kit.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class RecipeReferenceChecker implements KitCheckerInterface
{
    public function check(Kit $kit): iterable
    {
        foreach ($kit->getRecipes() as $recipe) {
            foreach ($recipe->manifest->dependencies as $dependency) {
                if (!$dependency instanceof RecipeDependency) {
                    continue;
                }

                if (null === $kit->getRecipe($dependency->name)) {
                    yield new LintIssue(
                        severity: LintSeverity::Error,
                        category: 'dependencies.recipe.unknown',
                        message: \sprintf('Recipe dependency "%s" does not exist in this kit.', $dependency->name),
                        recipe: $recipe->name,
                    );
                }
            }
        }
    }
}
