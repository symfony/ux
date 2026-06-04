<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Toolkit\Command\LintKitCommand;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;
use Symfony\UX\Toolkit\Kit\Lint\KitLinter;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;
use Zenstruck\Console\Test\TestCommand;

class LintKitCommandTest extends TestCase
{
    use TestHelperTrait;

    private function createCommand(): LintKitCommand
    {
        $filesystem = new Filesystem();
        $kitFactory = new KitFactory($filesystem, new KitSynchronizer($filesystem, new RecipeSynchronizer()));

        return new LintKitCommand($kitFactory, new KitLinter());
    }

    public function testLintBrokenFixtureReportsErrors()
    {
        $result = TestCommand::for($this->createCommand())
            ->addArgument(self::getFixtureKitPath('lint-broken'))
            ->execute()
            ->assertFaulty()
        ;

        $output = str_replace("\r\n", "\n", $result->output());

        self::assertStringContainsString(<<<TXT
            button
            ------

            ❌ copy-files.missing (missing-dir/)
                copy-files source "missing-dir/" does not exist.

            ❌ dependencies.recipe.unknown
                Recipe dependency "does-not-exist" does not exist in this kit.
            TXT, $output);

        self::assertStringContainsString(<<<TXT
            orphan
            ------

            ❌ manifest.missing
                Directory "orphan/" looks like a recipe but contains no "manifest.json".
            TXT, $output);

        self::assertStringContainsString(<<<TXT
            widget
            ------

            ⚠️ js.import.undeclared (assets/controllers/widget_controller.js)
                JS import "lodash" is not declared in dependencies.npm or dependencies.importmap.

            ⚠️ stimulus.controller.missing (components/Widget.html.twig)
                Template references Stimulus controller "undeclared-widget" but no matching file "assets/controllers/undeclared_widget_controller.js" was found in the recipe.
            TXT, $output);

        self::assertStringContainsString('[ERROR] 3 error(s), 3 warning(s).', $output);
    }

    public function testWarningsOnlyExitsSuccessfullyByDefault()
    {
        TestCommand::for($this->createCommand())
            ->addArgument(self::getFixtureKitPath('lint-stimulus'))
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('[WARNING]')
            ->assertOutputNotContains('[ERROR]')
        ;
    }

    public function testFailOnWarningExitsAsFailure()
    {
        TestCommand::for($this->createCommand())
            ->addArgument(self::getFixtureKitPath('lint-stimulus'))
            ->addOption('--fail-on-warning')
            ->execute()
            ->assertFaulty()
            ->assertOutputContains('[ERROR]')
        ;
    }
}
