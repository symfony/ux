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
use Symfony\UX\Toolkit\Kit\Lint\Checker\ComponentDocChecker;
use Symfony\UX\Toolkit\Kit\Lint\KitLinter;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintReport;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

class ComponentDocCheckerTest extends TestCase
{
    use TestHelperTrait;

    private function lintCases(): LintReport
    {
        $factory = new KitFactory(new Filesystem(), new KitSynchronizer(new Filesystem(), new RecipeSynchronizer()));
        $kit = $factory->createKitFromAbsolutePath(self::getFixtureKitPath('lint-component-doc'));

        return new KitLinter([new ComponentDocChecker()])->lint($kit);
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

    public function testValidComponentProducesNoIssues()
    {
        self::assertSame([], $this->issuesForFile($this->lintCases(), 'Valid.html.twig'));
    }

    public function testAllIssuesAreWarnings()
    {
        foreach ($this->lintCases()->getIssues() as $issue) {
            self::assertSame(LintSeverity::Warning, $issue->severity);
        }
    }

    public function testInvalidPropNameIsReported()
    {
        $issues = $this->issuesForFile($this->lintCases(), 'InvalidName.html.twig');

        self::assertCount(1, $issues);
        self::assertSame('component.prop.invalid', $issues[0]->category);
        self::assertStringContainsString('Bad_Name', $issues[0]->message);
    }

    public function testInvalidPhpDocTypeIsReported()
    {
        $issues = $this->issuesForFile($this->lintCases(), 'InvalidType.html.twig');

        self::assertCount(1, $issues);
        self::assertSame('component.prop.invalid', $issues[0]->category);
        self::assertStringContainsString('string|array<string', $issues[0]->message);
    }

    public function testDescriptionMustStartWithCapitalAndEndWithPeriod()
    {
        $issues = $this->issuesForFile($this->lintCases(), 'BadDescription.html.twig');

        self::assertCount(2, $issues);
        foreach ($issues as $issue) {
            self::assertSame('component.prop.invalid', $issue->category);
        }
    }

    public function testDeclaredPropWithoutDocblockIsReported()
    {
        $issues = $this->issuesForFile($this->lintCases(), 'MissingDoc.html.twig');

        self::assertCount(1, $issues);
        self::assertSame('component.prop.mismatch', $issues[0]->category);
        self::assertStringContainsString('hidden', $issues[0]->message);
    }

    public function testDocumentedPropNotDeclaredIsReported()
    {
        $issues = $this->issuesForFile($this->lintCases(), 'ExtraDoc.html.twig');

        self::assertCount(1, $issues);
        self::assertSame('component.prop.mismatch', $issues[0]->category);
        self::assertStringContainsString('ghost', $issues[0]->message);
    }

    public function testComponentWithPropsButNoDocblocksReportsEveryDeclaredProp()
    {
        $issues = $this->issuesForFile($this->lintCases(), 'NoDocblocks.html.twig');

        self::assertCount(2, $issues);
        foreach ($issues as $issue) {
            self::assertSame('component.prop.mismatch', $issue->category);
        }
        self::assertStringContainsString('foo', $issues[0]->message.$issues[1]->message);
        self::assertStringContainsString('bar', $issues[0]->message.$issues[1]->message);
    }

    public function testValidBlockProducesNoIssues()
    {
        self::assertSame([], $this->issuesForFile($this->lintCases(), 'ValidBlock.html.twig'));
    }

    public function testBlockDescriptionMustEndWithPeriod()
    {
        $issues = $this->issuesForFile($this->lintCases(), 'BlockMissingPeriod.html.twig');

        self::assertCount(1, $issues);
        self::assertSame('component.block.invalid', $issues[0]->category);
    }

    public function testDocumentedBlockNotUsedInTemplateIsReported()
    {
        $issues = $this->issuesForFile($this->lintCases(), 'BlockDocumentedNotUsed.html.twig');

        self::assertCount(1, $issues);
        self::assertSame('component.block.mismatch', $issues[0]->category);
        self::assertStringContainsString('ghost', $issues[0]->message);
    }

    public function testBlockUsedInTemplatebutNotDocumentedIsReported()
    {
        $issues = $this->issuesForFile($this->lintCases(), 'BlockUsedNotDocumented.html.twig');

        self::assertCount(1, $issues);
        self::assertSame('component.block.mismatch', $issues[0]->category);
        self::assertStringContainsString('content', $issues[0]->message);
    }

    public function testBlockRenderedViaOuterBlocksIsRecognizedAsUsed()
    {
        self::assertSame([], $this->issuesForFile($this->lintCases(), 'BlockViaOuterBlocks.html.twig'));
    }
}
