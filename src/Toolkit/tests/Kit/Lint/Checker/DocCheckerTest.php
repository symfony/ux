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
use Symfony\UX\Toolkit\Kit\Lint\Checker\DocChecker;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeManifest;
use Symfony\UX\Toolkit\Recipe\RecipeType;

final class DocCheckerTest extends TestCase
{
    public function testFlagsOnlyRecipesWithoutDoc()
    {
        $kit = new Kit(__DIR__, new KitManifest('kit', 'A kit', 'MIT', 'https://example.com'));
        $kit->addRecipe(new Recipe('with-doc', __DIR__, $this->recipeManifest('WithDoc'), doc: '# Docs'));
        $kit->addRecipe(new Recipe('without-doc', __DIR__, $this->recipeManifest('WithoutDoc')));

        $issues = iterator_to_array(new DocChecker()->check($kit));

        $this->assertCount(1, $issues);
        $this->assertSame(LintSeverity::Warning, $issues[0]->severity);
        $this->assertSame('doc.missing', $issues[0]->category);
        $this->assertSame('without-doc', $issues[0]->recipe);
    }

    private function recipeManifest(string $name): RecipeManifest
    {
        return new RecipeManifest(RecipeType::Component, $name, []);
    }
}
