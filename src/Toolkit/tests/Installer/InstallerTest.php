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
use Symfony\UX\Toolkit\Installer\Installer;
use Symfony\UX\Toolkit\Kit\Kit;

final class InstallerTest extends KernelTestCase
{
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

    public function testCanInstallComponent()
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

    public function testShouldAskIfFileAlreadyExists()
    {
        $askedCount = 0;
        $installer = new Installer(self::getContainer()->get('filesystem'), static function () use (&$askedCount) {
            ++$askedCount;

            return true;
        });
        $kit = $this->createKit('shadcn');

        $recipe = $kit->getRecipe('button');
        $this->assertNotNull($recipe);

        $installer->installRecipe($kit, $recipe, $this->tmpDir, false);

        $this->assertSame(0, $askedCount);
        $this->assertFileExists($this->tmpDir.'/templates/components//Button.html.twig');
        $this->assertSame(file_get_contents($this->tmpDir.'/templates/components//Button.html.twig'), file_get_contents(\sprintf('%s/templates/components/Button.html.twig', $recipe->absolutePath)));

        $installer->installRecipe($kit, $recipe, $this->tmpDir, false);
        $this->assertSame(1, $askedCount);
    }

    public function testCanInstallComponentIfForced()
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

    public function testCanInstallComponentAndItsComponentDependencies()
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
        return self::getContainer()->get('ux_toolkit.kit.kit_factory')->createKitFromAbsolutePath(Path::join(__DIR__, '../../kits', $kitName));
    }
}
