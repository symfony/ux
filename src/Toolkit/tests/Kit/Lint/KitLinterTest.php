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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;
use Symfony\UX\Toolkit\Kit\Lint\Checker\ClassMergeSpacingChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\ComposerSymbolChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\CopyFilesExistenceChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\JsImportChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\MissingRecipeManifestChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\RecipeReferenceChecker;
use Symfony\UX\Toolkit\Kit\Lint\Checker\StimulusControllerChecker;
use Symfony\UX\Toolkit\Kit\Lint\KitLinter;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintReport;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

class KitLinterTest extends TestCase
{
    use TestHelperTrait;

    private function loadFixtureKit(string $name): Kit
    {
        $factory = new KitFactory(new Filesystem(), new KitSynchronizer(new Filesystem(), new RecipeSynchronizer()));

        return $factory->createKitFromAbsolutePath(self::getFixtureKitPath($name));
    }

    private function loadBrokenKit(): Kit
    {
        return $this->loadFixtureKit('lint-broken');
    }

    private function findIssues(LintReport $report, string $category, ?string $recipe = null): array
    {
        return array_values(array_filter($report->getIssues(), static function (LintIssue $issue) use ($category, $recipe) {
            if ($issue->category !== $category) {
                return false;
            }
            if (null !== $recipe && $issue->recipe !== $recipe) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @return list<LintIssue>
     */
    private function findRecipeIssues(LintReport $report, string $recipe): array
    {
        return array_values(array_filter(
            $report->getIssues(),
            static fn (LintIssue $i) => $i->recipe === $recipe,
        ));
    }

    public function testMissingRecipeManifestCheckerDetectsOrphanDirectory()
    {
        $kit = $this->loadBrokenKit();
        $linter = new KitLinter([new MissingRecipeManifestChecker()]);

        $issues = $this->findIssues($linter->lint($kit), 'manifest.missing');

        $this->assertCount(1, $issues);
        $this->assertSame('orphan', $issues[0]->recipe);
        $this->assertSame(LintSeverity::Error, $issues[0]->severity);
    }

    public function testCopyFilesExistenceCheckerDetectsMissingDirectory()
    {
        $kit = $this->loadBrokenKit();
        $linter = new KitLinter([new CopyFilesExistenceChecker()]);

        $issues = $this->findIssues($linter->lint($kit), 'copy-files.missing', 'button');

        $this->assertCount(1, $issues);
        $this->assertSame('missing-dir/', $issues[0]->file);
        $this->assertSame(LintSeverity::Error, $issues[0]->severity);
    }

    public function testRecipeReferenceCheckerDetectsUnknownRecipe()
    {
        $kit = $this->loadBrokenKit();
        $linter = new KitLinter([new RecipeReferenceChecker()]);

        $issues = $this->findIssues($linter->lint($kit), 'dependencies.recipe.unknown', 'button');

        $this->assertCount(1, $issues);
        $this->assertStringContainsString('does-not-exist', $issues[0]->message);
    }

    /**
     * @return iterable<string, array{string, bool, ?string}>
     */
    public static function provideStimulusCases(): iterable
    {
        // controller name => [expected warning? , source template]
        yield 'HTML attribute form' => ['html-form-ctrl', true, 'HtmlForm.html.twig'];
        yield 'Twig hash form' => ['hash-form-ctrl', true, 'HashForm.html.twig'];
        yield 'mixed forms (HTML)' => ['mixed-html', true, 'Mixed.html.twig'];
        yield 'mixed forms (hash)' => ['mixed-hash', true, 'Mixed.html.twig'];
        yield 'space-separated (1st)' => ['multi-a', true, 'Multi.html.twig'];
        yield 'space-separated (2nd)' => ['multi-b', true, 'Multi.html.twig'];
        yield 'shipped controller' => ['shipped-ctrl', false, null];
    }

    #[DataProvider('provideStimulusCases')]
    public function testStimulusControllerChecker(string $controllerName, bool $expectWarning, ?string $expectedFile)
    {
        $kit = $this->loadFixtureKit('lint-stimulus');

        $report = new KitLinter([new StimulusControllerChecker()])->lint($kit);

        $matching = array_values(array_filter(
            $report->getIssues(),
            static fn (LintIssue $i) => 'stimulus.controller.missing' === $i->category
                && str_contains($i->message, \sprintf('"%s"', $controllerName))
        ));

        if (!$expectWarning) {
            $this->assertSame([], $matching, \sprintf('Controller "%s" must NOT be reported.', $controllerName));

            return;
        }

        $this->assertCount(1, $matching, \sprintf('Controller "%s" must be reported exactly once.', $controllerName));
        $this->assertSame(LintSeverity::Warning, $matching[0]->severity);
        $this->assertSame('cases', $matching[0]->recipe);
        $this->assertStringContainsString($expectedFile, $matching[0]->file);
    }

    public function testStimulusControllerSatisfiedByRecipeDependency()
    {
        $kit = $this->loadFixtureKit('lint-stimulus');

        $issues = new KitLinter([new StimulusControllerChecker()])->lint($kit)->getIssues();

        $usesDepIssues = array_values(array_filter(
            $issues,
            static fn (LintIssue $i) => 'stimulus.controller.missing' === $i->category && 'uses-dep' === $i->recipe,
        ));
        $this->assertSame([], $usesDepIssues, 'Controller shipped by a recipe declared in dependencies.recipe must not be reported.');

        $missingDepIssues = array_values(array_filter(
            $issues,
            static fn (LintIssue $i) => 'stimulus.controller.missing' === $i->category && 'missing-dep' === $i->recipe,
        ));
        $this->assertCount(1, $missingDepIssues, 'A controller not shipped by any declared recipe dependency must still be reported.');
        $this->assertStringContainsString('not-shipped-anywhere', $missingDepIssues[0]->message);
    }

    /**
     * @return iterable<string, array{string, ?string, ?string}>
     */
    public static function provideComposerCases(): iterable
    {
        // recipe slug => [expected category | null, needle in message | null]
        yield 'cva-ok declared+used' => ['cva-ok', null, null];
        yield 'classes-ok declared+used' => ['classes-ok', null, null];
        yield 'merge-ok declared+used (filter)' => ['merge-ok', null, null];
        yield 'icon-fn-ok declared+used (fn)' => ['icon-fn-ok', null, null];
        yield 'icon-tag-ok declared+used (tag)' => ['icon-tag-ok', null, null];
        yield 'map-ok declared+used' => ['map-ok', null, null];
        yield 'provide-ok declared+used' => ['provide-ok', null, null];
        yield 'inject-ok declared+used' => ['inject-ok', null, null];
        yield 'merge declared but unused' => ['merge-declared-unused', 'composer.declared-but-unused', 'tales-from-a-dev/twig-tailwind-extra'];
        yield 'icon declared but unused' => ['icon-declared-unused', 'composer.declared-but-unused', 'symfony/ux-icons'];
        yield 'map declared but unused' => ['map-declared-unused', 'composer.declared-but-unused', 'symfony/ux-map'];
        yield 'html_cva used but undeclared' => ['cva-undeclared', 'composer.symbol-undeclared', 'html_cva'];
        yield 'tailwind_merge used but undeclared' => ['merge-undeclared', 'composer.symbol-undeclared', 'tailwind_merge'];
        yield '<twig:ux:icon used but undeclared' => ['icon-tag-undeclared', 'composer.symbol-undeclared', '<twig:ux:icon'];
        yield '<twig:ux:map used but undeclared' => ['map-undeclared', 'composer.symbol-undeclared', '<twig:ux:map'];
        yield 'provide() used but undeclared' => ['provide-undeclared', 'composer.symbol-undeclared', 'provide('];
        yield 'html_cva with sufficient version' => ['cva-version-ok', null, null];
        yield 'html_cva with version too low' => ['cva-version-too-low', 'composer.symbol-version-insufficient', 'need >=3.12'];
        yield 'html_attr_type with sufficient version' => ['attr-type-version-ok', null, null];
        yield 'html_attr_type with version too low' => ['attr-type-version-too-low', 'composer.symbol-version-insufficient', 'need >=3.24'];
    }

    #[DataProvider('provideComposerCases')]
    public function testComposerSymbolChecker(string $recipe, ?string $category, ?string $needle)
    {
        $kit = $this->loadFixtureKit('lint-composer-symbol');
        $issues = $this->findRecipeIssues(new KitLinter([new ComposerSymbolChecker()])->lint($kit), $recipe);

        if (null === $category) {
            $this->assertSame([], $issues, \sprintf('Recipe "%s" must produce no issue.', $recipe));

            return;
        }

        $this->assertCount(1, $issues, \sprintf('Recipe "%s" must produce exactly one issue.', $recipe));
        $this->assertSame($category, $issues[0]->category);
        $this->assertSame(LintSeverity::Warning, $issues[0]->severity);
        $this->assertStringContainsString($needle, $issues[0]->message);
    }

    /**
     * @return iterable<string, array{string, ?string, ?string}>
     */
    public static function provideJsImportCases(): iterable
    {
        // recipe slug => [expected category | null, expected package in message | null]
        yield 'npm declared' => ['npm-declared', null, null];
        yield 'importmap declared' => ['importmap-declared', null, null];
        yield 'kit-level dependency declared' => ['kit-level-declared', null, null];
        yield 'undeclared static import' => ['undeclared', 'js.import.undeclared', 'lodash'];
        yield 'relative paths ignored' => ['relative-ignored', null, null];
        yield 'implicit @hotwired/stimulus' => ['implicit-stimulus', null, null];
        yield 'implicit @hotwired/turbo' => ['implicit-turbo', null, null];
        yield 'implicit @symfony/stimulus-bundle' => ['implicit-bundle', null, null];
        yield 'scoped subpath collapses' => ['scoped-subpath', null, null];
        yield 'plain subpath collapses' => ['plain-subpath', null, null];
        yield 'dynamic import detected' => ['dynamic-import', 'js.import.undeclared', 'lodash'];
        yield 'require() detected' => ['require-import', 'js.import.undeclared', 'lodash'];
        yield 'imports in *.ts scanned' => ['ts-file', 'js.import.undeclared', 'lodash'];
        yield 'imports in *.mjs scanned' => ['mjs-file', 'js.import.undeclared', 'lodash'];
        yield 'recipe without assets dir' => ['no-assets', null, null];
        yield 'noise in strings and comments' => ['import-in-string-noise', null, null];
        yield 'export ... from re-export' => ['export-re-export', 'js.import.undeclared', 'lodash'];
    }

    #[DataProvider('provideJsImportCases')]
    public function testJsImportChecker(string $recipe, ?string $category, ?string $needle)
    {
        $kit = $this->loadFixtureKit('lint-js-import');
        $issues = $this->findRecipeIssues(new KitLinter([new JsImportChecker()])->lint($kit), $recipe);

        if (null === $category) {
            $this->assertSame([], $issues, \sprintf('Recipe "%s" must produce no issue.', $recipe));

            return;
        }

        $this->assertCount(1, $issues, \sprintf('Recipe "%s" must produce exactly one issue.', $recipe));
        $this->assertSame($category, $issues[0]->category);
        $this->assertSame(LintSeverity::Warning, $issues[0]->severity);
        $this->assertStringContainsString($needle, $issues[0]->message);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideClassMergeCases(): iterable
    {
        // template file => number of expected issues
        yield 'literal ending with a space' => ['Valid.html.twig', 0];
        yield 'literal missing the trailing space' => ['MissingSpace.html.twig', 1];
        yield 'empty literal needs no separator' => ['EmptyLiteral.html.twig', 0];
        yield 'argument form (.apply) is not concatenation' => ['ApplyArg.html.twig', 0];
        yield 'double-quoted literal missing the trailing space' => ['DoubleQuote.html.twig', 1];
        yield 'one valid and one invalid concatenation' => ['Mixed.html.twig', 1];
    }

    #[DataProvider('provideClassMergeCases')]
    public function testClassMergeSpacingChecker(string $file, int $expectedCount)
    {
        $kit = $this->loadFixtureKit('lint-class-merge');

        $report = new KitLinter([new ClassMergeSpacingChecker()])->lint($kit);

        $matching = array_values(array_filter(
            $report->getIssues(),
            static fn (LintIssue $i) => 'component.class.missing-space' === $i->category
                && null !== $i->file && str_contains($i->file, $file),
        ));

        $this->assertCount($expectedCount, $matching, \sprintf('File "%s" must produce %d issue(s).', $file, $expectedCount));
        foreach ($matching as $issue) {
            $this->assertSame(LintSeverity::Error, $issue->severity);
            $this->assertSame('cases', $issue->recipe);
        }
    }
}
