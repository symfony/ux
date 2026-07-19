<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit\Lint;

use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\Lint\Checker\ClassMergeSpacingChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\ComponentDocChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\ComposerSymbolChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\CopyFilesExistenceChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\DocHeadingLevelChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\JsImportChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\MissingRecipeManifestChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\RecipeReferenceChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\StimulusControllerChecker;

/**
 * Runs every registered {@see KitCheckerInterface} against a kit and aggregates issues.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class KitLinter
{
    /**
     * @var array<KitCheckerInterface>
     */
    private readonly array $checkers;

    /**
     * @param array<KitCheckerInterface> $checkers when empty, every built-in checker is used
     */
    public function __construct(array $checkers = [])
    {
        $this->checkers = [] !== $checkers ? $checkers : [
            new MissingRecipeManifestChecker(),
            new CopyFilesExistenceChecker(),
            new RecipeReferenceChecker(),
            new StimulusControllerChecker(),
            new JsImportChecker(),
            new ComposerSymbolChecker(),
            new ComponentDocChecker(),
            new ClassMergeSpacingChecker(),
            new DocHeadingLevelChecker(),
        ];
    }

    public function lint(Kit $kit): LintReport
    {
        $report = new LintReport();

        foreach ($this->checkers as $checker) {
            foreach ($checker->check($kit) as $issue) {
                $report->add($issue);
            }
        }

        return $report;
    }
}
