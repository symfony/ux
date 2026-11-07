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
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;

/**
 * Verifies that every Stimulus controller shipped by a recipe is lazy-loaded.
 *
 * Without the annotation, StimulusBundle loads an installed controller and its dependencies on every page,
 * even on pages that never render the component.
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 *
 * @internal
 */
final class StimulusControllerLazyChecker implements KitCheckerInterface
{
    /**
     * The pattern StimulusBundle uses to detect a lazy controller.
     */
    private const LAZY_PATTERN = '/\/\*!?\s*stimulusFetch:\s*\'lazy\'\s*\*\//i';

    public function check(Kit $kit): iterable
    {
        foreach ($kit->getRecipes() as $recipe) {
            $dir = Path::join($recipe->absolutePath, 'assets/controllers');
            if (!is_dir($dir)) {
                continue;
            }

            $finder = new Finder()->in($dir)->files()->name('*_controller.js')->sortByName();
            foreach ($finder as $file) {
                if (preg_match(self::LAZY_PATTERN, $file->getContents())) {
                    continue;
                }

                yield new LintIssue(
                    severity: LintSeverity::Warning,
                    category: 'stimulus.controller.eager',
                    message: "Stimulus controller is loaded on every page. Add the \"/* stimulusFetch: 'lazy' */\" annotation to load it only when the page uses it.",
                    recipe: $recipe->name,
                    file: 'assets/controllers/'.str_replace('\\', '/', $file->getRelativePathname()),
                );
            }
        }
    }
}
