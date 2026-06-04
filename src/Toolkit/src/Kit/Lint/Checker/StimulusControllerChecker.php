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

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\UX\Toolkit\Kit\Kit;

use function Symfony\Component\String\u;
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;
use Symfony\UX\Toolkit\Recipe\Recipe;

/**
 * Detects `data-controller="..."` usages in Twig templates and verifies that a matching
 * Stimulus controller file is shipped with the recipe.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class StimulusControllerChecker implements KitCheckerInterface
{
    public function check(Kit $kit): iterable
    {
        foreach ($kit->getRecipes() as $recipe) {
            $templatesDir = Path::join($recipe->absolutePath, 'templates');
            if (!is_dir($templatesDir)) {
                continue;
            }

            $controllerNames = $this->extractControllerNames($templatesDir);
            if ([] === $controllerNames) {
                continue;
            }

            $shippedControllers = $this->listShippedControllers($recipe);

            foreach ($controllerNames as $controllerName => $file) {
                if (\in_array($controllerName, $shippedControllers, true)) {
                    continue;
                }

                yield new LintIssue(
                    severity: LintSeverity::Warning,
                    category: 'stimulus.controller.missing',
                    message: \sprintf(
                        'Template references Stimulus controller "%s" but no matching file "assets/controllers/%s_controller.js" was found in the recipe.',
                        $controllerName,
                        // Stimulus convention: kebab-case identifier <-> snake_case filename.
                        u($controllerName)->snake(),
                    ),
                    recipe: $recipe->name,
                    file: $file,
                );
            }
        }
    }

    /**
     * @return array<string, string> controller name => first file where it was found
     */
    private function extractControllerNames(string $templatesDir): array
    {
        $names = [];
        $patterns = [
            // Twig hash form: {'data-controller': 'foo'} or {"data-controller": "foo"}
            '/[\'"]data-controller[\'"]\s*[:=]\s*[\'"]([^\'"]+)[\'"]/',
            // HTML attribute form: data-controller="foo"
            '/data-controller\s*=\s*[\'"]([^\'"]+)[\'"]/',
        ];

        $finder = new Finder()->in($templatesDir)->files()->name('*.html.twig');
        foreach ($finder as $file) {
            $contents = $file->getContents();
            foreach ($patterns as $pattern) {
                if (!preg_match_all($pattern, $contents, $matches)) {
                    continue;
                }
                foreach ($matches[1] as $value) {
                    foreach (preg_split('/\s+/', trim($value)) as $name) {
                        if ('' === $name) {
                            continue;
                        }
                        $names[$name] ??= $file->getRelativePathname();
                    }
                }
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function listShippedControllers(Recipe $recipe): array
    {
        $dir = Path::join($recipe->absolutePath, 'assets/controllers');
        if (!is_dir($dir)) {
            return [];
        }

        $controllers = [];
        $finder = new Finder()->in($dir)->files()->name('*_controller.js');
        foreach ($finder as $file) {
            $basename = substr($file->getFilename(), 0, -\strlen('_controller.js'));
            // Stimulus convention: snake_case in filename, kebab-case in identifier.
            $controllers[] = (string) u($basename)->kebab();
        }

        return $controllers;
    }
}
