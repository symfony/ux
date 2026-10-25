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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitManifest;
use Symfony\UX\Toolkit\Kit\Lint\Checker\CopyFilesPlaceholderChecker;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeManifest;
use Symfony\UX\Toolkit\Recipe\RecipeType;

final class CopyFilesPlaceholderCheckerTest extends TestCase
{
    /**
     * @return iterable<string, array{array<string, string>, list<string>}>
     */
    public static function provideCopyFiles(): iterable
    {
        yield 'placeholders' => [['templates/components/' => '%COMPONENTS_DIR%/', 'assets/controllers/' => '%CONTROLLERS_DIR%/'], []];
        yield 'parent directories' => [['templates/' => 'templates/', 'assets/' => 'assets/'], ['%COMPONENTS_DIR%', '%CONTROLLERS_DIR%']];
        yield 'default directories' => [['templates/components/' => 'templates/components/', 'assets/controllers/' => 'assets/controllers/'], ['%COMPONENTS_DIR%', '%CONTROLLERS_DIR%']];
        yield 'file in a default directory' => [['assets/controllers/widget_controller.js' => 'assets/controllers/widget_controller.js'], ['%CONTROLLERS_DIR%']];
        yield 'parent directory of an unrelated directory' => [['assets/controllers/' => 'assets/scripts/'], []];
        yield 'parent directory without the default directory' => [['templates/components/' => 'templates/'], []];
    }

    /**
     * @param array<string, string> $copyFiles
     * @param list<string>          $expectedPlaceholders
     */
    #[DataProvider('provideCopyFiles')]
    public function testSuggestsAPlaceholderForADefaultDirectory(array $copyFiles, array $expectedPlaceholders): void
    {
        $kit = new Kit(__DIR__, new KitManifest('kit', 'A kit', 'MIT', 'https://example.com'));
        $recipePath = Path::join(__DIR__, '../../../Fixtures/kits/lint-broken/widget');
        $kit->addRecipe(new Recipe('widget', $recipePath, new RecipeManifest(RecipeType::Component, 'Widget', $copyFiles)));

        $issues = iterator_to_array(new CopyFilesPlaceholderChecker()->check($kit), false);

        $this->assertSame($expectedPlaceholders, array_map(static function (LintIssue $issue): string {
            preg_match('/"(%[A-Z_]+%)"/', $issue->message, $matches);

            return $matches[1];
        }, $issues));
        foreach ($issues as $issue) {
            $this->assertSame(LintSeverity::Warning, $issue->severity);
            $this->assertSame('copy-files.placeholder', $issue->category);
            $this->assertSame('widget', $issue->recipe);
        }
    }
}
