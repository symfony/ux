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

    public function testCanInstallComponent(): void
    {
        $componentInstaller = new Installer(self::getContainer()->get('filesystem'), fn () => throw new \BadFunctionCallException('The installer should not ask for confirmation since the file does not exist.'));
        $kit = $this->createKit('shadcn');

        $this->assertFileDoesNotExist($this->tmpDir.'/Button.html.twig');

        $component = $kit->getComponent('Button');
        $this->assertNotNull($component);

        $componentInstaller->installComponent($kit, $component, $this->tmpDir, false);

        $this->assertFileExists($this->tmpDir.'/Button.html.twig');
        $this->assertSame($this->filesystem->readFile($this->tmpDir.'/Button.html.twig'), $this->filesystem->readFile(\sprintf('%s/templates/components/Button.html.twig', $kit->path)));
    }

    public function testShouldAskIfFileAlreadyExists(): void
    {
        $askedCount = 0;
        $componentInstaller = new Installer(self::getContainer()->get('filesystem'), function () use (&$askedCount) {
            ++$askedCount;

            return true;
        });
        $kit = $this->createKit('shadcn');

        $component = $kit->getComponent('Button');
        $this->assertNotNull($component);

        $componentInstaller->installComponent($kit, $component, $this->tmpDir, false);

        $this->assertSame(0, $askedCount);
        $this->assertFileExists($this->tmpDir.'/Button.html.twig');
        $this->assertSame($this->filesystem->readFile($this->tmpDir.'/Button.html.twig'), $this->filesystem->readFile(\sprintf('%s/templates/components/Button.html.twig', $kit->path)));

        $componentInstaller->installComponent($kit, $component, $this->tmpDir, false);
        $this->assertSame(1, $askedCount);
    }

    public function testCanInstallComponentIfForced(): void
    {
        $componentInstaller = new Installer(self::getContainer()->get('filesystem'), fn () => throw new \BadFunctionCallException('The installer should not ask for confirmation since the file does not exist.'));
        $kit = $this->createKit('shadcn');

        $component = $kit->getComponent('Button');
        $this->assertNotNull($component);

        $componentInstaller->installComponent($kit, $component, $this->tmpDir, false);

        $this->assertFileExists($this->tmpDir.'/Button.html.twig');
        $this->assertSame($this->filesystem->readFile($this->tmpDir.'/Button.html.twig'), $this->filesystem->readFile(\sprintf('%s/templates/components/Button.html.twig', $kit->path)));

        $componentInstaller->installComponent($kit, $component, $this->tmpDir, true);

        $this->assertFileExists($this->tmpDir.'/Button.html.twig');
        $this->assertSame($this->filesystem->readFile($this->tmpDir.'/Button.html.twig'), $this->filesystem->readFile(\sprintf('%s/templates/components/Button.html.twig', $kit->path)));
    }

    public function testCanInstallComponentAndItsComponentDependencies(): void
    {
        $componentInstaller = new Installer(self::getContainer()->get('filesystem'), fn () => throw new \BadFunctionCallException('The installer should not ask for confirmation since the file does not exist.'));
        $kit = $this->createKit('shadcn');

        $expectedFiles = [
            'Table.html.twig' => $this->tmpDir.'/Table.html.twig',
            'Table/Body.html.twig' => $this->tmpDir.'/Table/Body.html.twig',
            'Table/Caption.html.twig' => $this->tmpDir.'/Table/Caption.html.twig',
            'Table/Cell.html.twig' => $this->tmpDir.'/Table/Cell.html.twig',
            'Table/Footer.html.twig' => $this->tmpDir.'/Table/Footer.html.twig',
            'Table/Head.html.twig' => $this->tmpDir.'/Table/Head.html.twig',
            'Table/Header.html.twig' => $this->tmpDir.'/Table/Header.html.twig',
            'Table/Row.html.twig' => $this->tmpDir.'/Table/Row.html.twig',
            'Button.html.twig' => $this->tmpDir.'/Button.html.twig',
            'Input.html.twig' => $this->tmpDir.'/Input.html.twig',
        ];

        foreach ($expectedFiles as $expectedFile) {
            $this->assertFileDoesNotExist($expectedFile);
        }

        $componentInstaller->installComponent($kit, $kit->getComponent('Table'), $this->tmpDir, false);
        $componentInstaller->installComponent($kit, $kit->getComponent('Button'), $this->tmpDir, false);
        $componentInstaller->installComponent($kit, $kit->getComponent('Input'), $this->tmpDir, false);

        foreach ($expectedFiles as $fileName => $expectedFile) {
            $this->assertFileExists($expectedFile);
            $this->assertSame($this->filesystem->readFile($expectedFile), $this->filesystem->readFile(\sprintf('%s/templates/components/%s', $kit->path, $fileName)));
        }
    }

    private function createKit(string $kitName): Kit
    {
        return self::getContainer()->get('ux_toolkit.kit.kit_factory')->createKitFromAbsolutePath(Path::join(__DIR__, '../../kits', $kitName));
    }
}
