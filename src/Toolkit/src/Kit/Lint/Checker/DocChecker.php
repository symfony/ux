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

use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;

/**
 * Warns about recipes shipping no "doc.md"; their documentation is then generated
 * from the manifest and examples only, without any authored narrative.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class DocChecker implements KitCheckerInterface
{
    public function check(Kit $kit): iterable
    {
        foreach ($kit->getRecipes() as $recipe) {
            if (null === $recipe->doc) {
                yield new LintIssue(
                    severity: LintSeverity::Warning,
                    category: 'doc.missing',
                    message: 'Recipe has no "doc.md"; its documentation is generated from the manifest and examples only.',
                    recipe: $recipe->name,
                );
            }
        }
    }
}
