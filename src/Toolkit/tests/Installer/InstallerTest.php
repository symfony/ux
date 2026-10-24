<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Installer;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\File;
use Symfony\UX\Toolkit\Installer\InstallationDirectories;
use Symfony\UX\Toolkit\Installer\Installer;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

final class InstallerTest extends KernelTestCase
{
    use TestHelperTrait;

    private Filesystem $filesystem;
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootKernel();
        $this->filesystem = self::getContainer()->get('filesystem');
        $this->tmpDir = $this->filesystem->tempnam(sys_get_temp_dir(), 'ux_toolkit_test_');
        $this->filesystem->remove($this->tmpDir);
        $this->filesystem->mkdir($this->tmpDir);
    }

    public function testCanInstallComponent(): void
    {
        $installer = new Installer(self::getContainer()->get('filesystem'), static fn () => throw new \BadFunctionCallException('The installer should not ask for confirmation since the file does not exist.'));
        $kit = $this->createKit('shadcn');

        $this->assertFileDoesNotExist($this->tmpDir.'/Button.html.twig');

        $recipe = $kit->getRecipe('button');
        $this->assertNotNull($recipe);

        $installer->installRecipe($kit, $recipe, $this->tmpDir, false);

        $this->assertFileExists($this->tmpDir.'/templates/components/Button.html.twig');
        $this->assertSame(file_get_contents($this->tmpDir.'/templates/components/Button.html.twig'), file_get_contents(\sprintf('%s/templates/components/Button.html.twig', $recipe->absolutePath)));
    }

    public function testShouldAskIfFileAlreadyExists(): void
    {
        $askedFiles = [];
        $askConfirmation = static function (string $question, string $existingFile, string $newContents) use (&$askedFiles) {
            $askedFiles[] = [$existingFile, $newContents];

            return true;
        };
        $installer = new Installer(self::getContainer()->get('filesystem'), $askConfirmation);
        $kit = $this->createKit('shadcn');

        $recipe = $kit->getRecipe('button');
        $this->assertNotNull($recipe);

        $installer->installRecipe($kit, $recipe, $this->tmpDir, false);

        $this->assertSame([], $askedFiles);
        $this->assertFileExists($this->tmpDir.'/templates/components/Button.html.twig');
        $this->assertSame(file_get_contents($this->tmpDir.'/templates/components/Button.html.twig'), file_get_contents(\sprintf('%s/templates/components/Button.html.twig', $recipe->absolutePath)));

        $this->filesystem->dumpFile($this->tmpDir.'/templates/components/Button.html.twig', 'Local changes');
        $installer->installRecipe($kit, $recipe, $this->tmpDir, false);

        $expectedExistingFile = Path::join($this->tmpDir, 'templates/components/Button.html.twig');
        $expectedNewContents = file_get_contents(Path::join($recipe->absolutePath, 'templates/components/Button.html.twig'));
        $this->assertSame([[$expectedExistingFile, $expectedNewContents]], $askedFiles);
    }

    public function testInstallsInTheConfiguredDirectories(): void
    {
        $installer = $this->createInstaller(new InstallationDirectories('templates/components', 'ui', 'assets/js/controllers', null));
        $kit = $this->createKit('shadcn');

        $installationReport = $installer->installRecipe($kit, $kit->getRecipe('dialog'), $this->tmpDir, false);

        $installedFiles = array_map(static fn (File $file) => $file->destinationRelativePathName, $installationReport->newFiles);
        $this->assertContains('templates/components/ui/Dialog.html.twig', $installedFiles);
        $this->assertContains('templates/components/ui/Dialog/Content.html.twig', $installedFiles);
        $this->assertContains('assets/js/controllers/dialog_controller.js', $installedFiles);
        $this->assertFileExists($this->tmpDir.'/templates/components/ui/Dialog/Content.html.twig');
        $this->assertFileExists($this->tmpDir.'/assets/js/controllers/dialog_controller.js');
        $this->assertDirectoryDoesNotExist($this->tmpDir.'/assets/controllers');
    }

    public function testPrefixesTheComponentsAndControllersInstalledInSubDirectories(): void
    {
        $installer = $this->createInstaller(new InstallationDirectories('templates/components', 'ui', 'assets/controllers', 'ui'));
        $kit = $this->createKit('shadcn');
        $recipe = $kit->getRecipe('alert-dialog');

        $installer->installRecipe($kit, $recipe, $this->tmpDir, false);

        $sourceAction = file_get_contents($recipe->absolutePath.'/templates/components/AlertDialog/Action.html.twig');
        $installedAction = file_get_contents($this->tmpDir.'/templates/components/ui/AlertDialog/Action.html.twig');
        $this->assertStringContainsString('<twig:Button', $sourceAction);
        $this->assertStringContainsString('<twig:ui:Button', $installedAction);
        $this->assertStringNotContainsString('<twig:Button', $installedAction);

        $sourceAlertDialog = file_get_contents($recipe->absolutePath.'/templates/components/AlertDialog.html.twig');
        $installedAlertDialog = file_get_contents($this->tmpDir.'/templates/components/ui/AlertDialog.html.twig');
        $this->assertMatchesRegularExpression('/data-controller[\'"]?\s*[:=]\s*[\'"]alert-dialog[\'"]/', $sourceAlertDialog);
        $this->assertMatchesRegularExpression('/data-controller[\'"]?\s*[:=]\s*[\'"]ui--alert-dialog[\'"]/', $installedAlertDialog);
        $this->assertFileExists($this->tmpDir.'/assets/controllers/ui/alert_dialog_controller.js');
    }

    public function testLeavesTheFilesCopiedWithoutPlaceholdersUntouched(): void
    {
        $installer = $this->createInstaller(new InstallationDirectories('templates/components', 'ui', 'assets/controllers', 'ui'));
        $kitFactory = self::getContainer()->get('ux_toolkit.kit.kit_factory');
        $kit = $kitFactory->createKitFromAbsolutePath(self::getFixtureKitPath('with-renamed-copy-files'));
        $recipe = $kit->getRecipe('notice');

        $installer->installRecipe($kit, $recipe, $this->tmpDir, false);

        $this->assertFileEquals($recipe->absolutePath.'/ui/Notice.html.twig', $this->tmpDir.'/templates/components/Notice.html.twig');
        $this->assertDirectoryDoesNotExist($this->tmpDir.'/templates/components/ui');
    }

    public function testAsksWithTheRewrittenContents(): void
    {
        $askedFiles = [];
        $askConfirmation = static function (string $question, string $existingFile, string $newContents) use (&$askedFiles) {
            $askedFiles[] = [$existingFile, $newContents];

            return false;
        };
        $directories = new InstallationDirectories('templates/components', 'ui', 'assets/controllers', 'ui');
        $installer = new Installer($this->filesystem, $askConfirmation, $directories);
        $kit = $this->createKit('shadcn');
        $recipe = $kit->getRecipe('alert-dialog');
        $installedAction = Path::join($this->tmpDir, 'templates/components/ui/AlertDialog/Action.html.twig');

        $installer->installRecipe($kit, $recipe, $this->tmpDir, false);
        $expectedNewContents = file_get_contents($installedAction);
        $this->filesystem->dumpFile($installedAction, 'Local changes');

        $installationReport = $installer->installRecipe($kit, $recipe, $this->tmpDir, false);

        $this->assertSame([[$installedAction, $expectedNewContents]], $askedFiles);
        $this->assertStringEqualsFile($installedAction, 'Local changes');
        $newFiles = array_map(static fn (File $file) => $file->destinationRelativePathName, $installationReport->newFiles);
        $this->assertNotContains('templates/components/ui/AlertDialog/Action.html.twig', $newFiles);
    }

    public function testShouldNotAskIfFileIsIdentical(): void
    {
        $installer = new Installer(self::getContainer()->get('filesystem'), static fn () => throw new \BadFunctionCallException('The installer should not ask for confirmation since the file is identical.'));
        $kit = $this->createKit('shadcn');
        $recipe = $kit->getRecipe('button');

        $installer->installRecipe($kit, $recipe, $this->tmpDir, false);
        $installationReport = $installer->installRecipe($kit, $recipe, $this->tmpDir, false);

        $installedFiles = array_map(static fn (File $file) => $file->destinationRelativePathName, $installationReport->newFiles);
        $this->assertSame(['templates/components/Button.html.twig'], $installedFiles);
    }

    public function testShouldOverwriteFileNewerThanRecipeWhenConfirmed(): void
    {
        $installer = new Installer(self::getContainer()->get('filesystem'), static fn () => true);
        $kit = $this->createKit('shadcn');
        $recipe = $kit->getRecipe('button');
        $recipeFile = $recipe->absolutePath.'/templates/components/Button.html.twig';
        $installedFile = $this->tmpDir.'/templates/components/Button.html.twig';

        $installer->installRecipe($kit, $recipe, $this->tmpDir, false);
        $this->filesystem->dumpFile($installedFile, 'Local changes');
        touch($installedFile, filemtime($recipeFile) + 3600);

        $installer->installRecipe($kit, $recipe, $this->tmpDir, false);

        $this->assertFileEquals($recipeFile, $installedFile);
    }

    public function testCanInstallComponentIfForced(): void
    {
        $installer = new Installer(self::getContainer()->get('filesystem'), static fn () => throw new \BadFunctionCallException('The installer should not ask for confirmation since the file does not exist.'));
        $kit = $this->createKit('shadcn');

        $recipe = $kit->getRecipe('button');
        $this->assertNotNull($recipe);

        $installer->installRecipe($kit, $recipe, $this->tmpDir, false);

        $this->assertFileExists($this->tmpDir.'/templates/components/Button.html.twig');
        $this->assertSame(file_get_contents($this->tmpDir.'/templates/components/Button.html.twig'), file_get_contents(\sprintf('%s/templates/components/Button.html.twig', $recipe->absolutePath)));

        $installer->installRecipe($kit, $recipe, $this->tmpDir, true);

        $this->assertFileExists($this->tmpDir.'/templates/components/Button.html.twig');
        $this->assertSame(file_get_contents($this->tmpDir.'/templates/components/Button.html.twig'), file_get_contents(\sprintf('%s/templates/components/Button.html.twig', $recipe->absolutePath)));
    }

    public function testCanInstallComponentAndItsComponentDependencies(): void
    {
        $installer = new Installer(self::getContainer()->get('filesystem'), static fn () => throw new \BadFunctionCallException('The installer should not ask for confirmation since the file does not exist.'));
        $kit = $this->createKit('shadcn');

        $expectedFiles = [
            'Table.html.twig' => $this->tmpDir.'/templates/components/Table.html.twig',
            'Table/Body.html.twig' => $this->tmpDir.'/templates/components/Table/Body.html.twig',
            'Table/Caption.html.twig' => $this->tmpDir.'/templates/components/Table/Caption.html.twig',
            'Table/Cell.html.twig' => $this->tmpDir.'/templates/components/Table/Cell.html.twig',
            'Table/Footer.html.twig' => $this->tmpDir.'/templates/components/Table/Footer.html.twig',
            'Table/Head.html.twig' => $this->tmpDir.'/templates/components/Table/Head.html.twig',
            'Table/Header.html.twig' => $this->tmpDir.'/templates/components/Table/Header.html.twig',
            'Table/Row.html.twig' => $this->tmpDir.'/templates/components/Table/Row.html.twig',
            'Button.html.twig' => $this->tmpDir.'/templates/components/Button.html.twig',
            'Input.html.twig' => $this->tmpDir.'/templates/components/Input.html.twig',
        ];

        foreach ($expectedFiles as $expectedFile) {
            $this->assertFileDoesNotExist($expectedFile);
        }

        $installer->installRecipe($kit, $kit->getRecipe('table'), $this->tmpDir, false);
        $installer->installRecipe($kit, $kit->getRecipe('button'), $this->tmpDir, false);
        $installer->installRecipe($kit, $kit->getRecipe('input'), $this->tmpDir, false);

        foreach ($expectedFiles as $expectedFile) {
            $this->assertFileExists($expectedFile);
        }
    }

    private function createKit(string $kitName): Kit
    {
        return self::getContainer()->get('ux_toolkit.kit.kit_factory')->createKitFromAbsolutePath(self::getLocalKitPath($kitName));
    }

    private function createInstaller(InstallationDirectories $directories): Installer
    {
        return new Installer(
            $this->filesystem,
            static fn () => throw new \BadFunctionCallException('The installer should not ask for confirmation.'),
            $directories,
        );
    }
}
