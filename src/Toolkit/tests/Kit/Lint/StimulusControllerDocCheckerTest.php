<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Kit\Lint;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;
use Symfony\UX\Toolkit\Kit\Lint\Checker\StimulusControllerDocChecker;
use Symfony\UX\Toolkit\Kit\Lint\KitLinter;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintReport;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

class StimulusControllerDocCheckerTest extends TestCase
{
    use TestHelperTrait;

    private function lintCases(): LintReport
    {
        $factory = new KitFactory(new Filesystem(), new KitSynchronizer(new Filesystem(), new RecipeSynchronizer()));
        $kit = $factory->createKitFromAbsolutePath(self::getFixtureKitPath('lint-stimulus-doc'));

        return new KitLinter([new StimulusControllerDocChecker()])->lint($kit);
    }

    /**
     * @return list<LintIssue>
     */
    private function issuesForFile(LintReport $report, string $fileNeedle): array
    {
        return array_values(array_filter(
            $report->getIssues(),
            static fn (LintIssue $i) => null !== $i->file && str_contains($i->file, $fileNeedle),
        ));
    }

    public function testValidControllerProducesNoIssues()
    {
        self::assertSame([], $this->issuesForFile($this->lintCases(), 'valid_controller.js'));
    }

    public function testUndocumentedControllerIsNotReported()
    {
        // Documentation is opt-in: a controller with no tags at all must be left alone.
        self::assertSame([], $this->issuesForFile($this->lintCases(), 'undocumented_controller.js'));
    }

    public function testAllIssuesAreWarnings()
    {
        foreach ($this->lintCases()->getIssues() as $issue) {
            self::assertSame(LintSeverity::Warning, $issue->severity);
        }
    }

    public function testMismatchesAreReportedBothWays()
    {
        $issues = $this->issuesForFile($this->lintCases(), 'mismatch_controller.js');
        $messages = array_map(static fn (LintIssue $i) => $i->message, $issues);

        self::assertCount(2, $issues);
        foreach ($issues as $issue) {
            self::assertSame('stimulus.value.mismatch', $issue->category);
        }
        self::assertStringContainsString('"ghost" is documented with `@value` but is not declared', implode("\n", $messages));
        self::assertStringContainsString('"autoClose" is declared in the controller but has no `@value', implode("\n", $messages));
    }

    public function testBadDescriptionIsReported()
    {
        $issues = $this->issuesForFile($this->lintCases(), 'bad_description_controller.js');

        self::assertCount(2, $issues);
        foreach ($issues as $issue) {
            self::assertSame('stimulus.value.invalid', $issue->category);
        }
    }

    public function testActionWithoutMatchingMethodIsReported()
    {
        $issues = $this->issuesForFile($this->lintCases(), 'orphan_action_controller.js');

        self::assertCount(1, $issues);
        self::assertSame('stimulus.action.mismatch', $issues[0]->category);
        self::assertStringContainsString('"save"', $issues[0]->message);
    }
}
