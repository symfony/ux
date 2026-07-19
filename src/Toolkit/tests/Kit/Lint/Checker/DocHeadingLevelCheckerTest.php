<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Kit\Lint\Checker;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitManifest;
use Symfony\UX\Toolkit\Kit\Lint\Checker\DocHeadingLevelChecker;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeManifest;
use Symfony\UX\Toolkit\Recipe\RecipeType;

final class DocHeadingLevelCheckerTest extends TestCase
{
    public function testRequiresASingleLeadingLevelOneHeading()
    {
        $kit = new Kit(__DIR__, new KitManifest('kit', 'A kit', 'MIT', 'https://example.com'));
        // Valid: opens with the title as the only level-1 heading; a `#` inside a fence is ignored.
        $kit->addRecipe(new Recipe('ok', __DIR__, $this->recipeManifest('Ok'), doc: "# Ok\n\n## Usage\n\n```md\n# not a heading\n```"));
        // Missing leading title: the first heading is level 2.
        $kit->addRecipe(new Recipe('no-title', __DIR__, $this->recipeManifest('No Title'), doc: "## Usage\n\nSome text."));
        // A second level-1 heading in the body.
        $kit->addRecipe(new Recipe('two-titles', __DIR__, $this->recipeManifest('Two Titles'), doc: "# Two Titles\n\n## Usage\n\n# Oops"));

        $issues = iterator_to_array(new DocHeadingLevelChecker()->check($kit));

        $this->assertCount(2, $issues);

        $byRecipe = [];
        foreach ($issues as $issue) {
            $this->assertSame(LintSeverity::Error, $issue->severity);
            $this->assertSame('doc.heading_level', $issue->category);
            $byRecipe[$issue->recipe] = $issue->message;
        }

        $this->assertArrayHasKey('no-title', $byRecipe);
        $this->assertStringContainsString('open with its title', $byRecipe['no-title']);
        $this->assertArrayHasKey('two-titles', $byRecipe);
        $this->assertStringContainsString('# Oops', $byRecipe['two-titles']);
    }

    private function recipeManifest(string $name): RecipeManifest
    {
        return new RecipeManifest(RecipeType::Component, $name, []);
    }
}
