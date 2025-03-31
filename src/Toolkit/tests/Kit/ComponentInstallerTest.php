<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Kit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Component\ComponentInstaller;
use Symfony\UX\Toolkit\Dependency\ComponentDependency;
use Symfony\UX\Toolkit\Exception\ComponentAlreadyExistsException;
use Symfony\UX\Toolkit\Kit\Kit;

final class ComponentInstallerTest extends KernelTestCase
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
        $componentInstaller = new ComponentInstaller(self::getContainer()->get('filesystem'));
        $kit = $this->createKit('shadcn');

        $this->assertFileDoesNotExist($this->tmpDir.'/Button.html.twig');

        $component = $kit->getComponent('Button');
        $this->assertNotNull($component);

        $componentInstaller->install($kit, $component, $this->tmpDir);

        $this->assertFileExists($this->tmpDir.'/Button.html.twig');
        $this->assertSame($this->filesystem->readFile($this->tmpDir.'/Button.html.twig'), $this->filesystem->readFile(\sprintf('%s/templates/components/Button.html.twig', $kit->path)));
    }

    public function testShouldFailIfComponentAlreadyExists(): void
    {
        $componentInstaller = new ComponentInstaller(self::getContainer()->get('filesystem'));
        $kit = $this->createKit('shadcn');

        $component = $kit->getComponent('Button');
        $this->assertNotNull($component);

        $componentInstaller->install($kit, $component, $this->tmpDir);

        $this->assertFileExists($this->tmpDir.'/Button.html.twig');
        $this->assertSame($this->filesystem->readFile($this->tmpDir.'/Button.html.twig'), $this->filesystem->readFile(\sprintf('%s/templates/components/Button.html.twig', $kit->path)));

        $this->expectException(ComponentAlreadyExistsException::class);
        $this->expectExceptionMessage('The component "Button" already exists.');

        $componentInstaller->install($kit, $component, $this->tmpDir);
    }

    public function testCanInstallComponentIfForced(): void
    {
        $componentInstaller = new ComponentInstaller(self::getContainer()->get('filesystem'));
        $kit = $this->createKit('shadcn');

        $component = $kit->getComponent('Button');
        $this->assertNotNull($component);

        $componentInstaller->install($kit, $component, $this->tmpDir);

        $this->assertFileExists($this->tmpDir.'/Button.html.twig');
        $this->assertSame($this->filesystem->readFile($this->tmpDir.'/Button.html.twig'), $this->filesystem->readFile(\sprintf('%s/templates/components/Button.html.twig', $kit->path)));

        // No exception should be thrown, the file should be overwritten
        $componentInstaller->install($kit, $component, $this->tmpDir, true);

        $this->assertFileExists($this->tmpDir.'/Button.html.twig');
        $this->assertSame($this->filesystem->readFile($this->tmpDir.'/Button.html.twig'), $this->filesystem->readFile(\sprintf('%s/templates/components/Button.html.twig', $kit->path)));
    }

    public function testCanInstallComponentAndItsComponentDependencies(): void
    {
        $componentInstaller = new ComponentInstaller(self::getContainer()->get('filesystem'));
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
        ];

        foreach ($expectedFiles as $expectedFile) {
            $this->assertFileDoesNotExist($expectedFile);
        }

        $component = $kit->getComponent('Table');
        $this->assertNotNull($component);

        // Install the component and its dependencies
        $componentInstaller->install($kit, $component, $this->tmpDir);
        foreach ($component->getDependencies() as $dependency) {
            if ($dependency instanceof ComponentDependency) {
                $dependencyComponent = $kit->getComponent($dependency->name);
                $this->assertNotNull($dependencyComponent);

                $componentInstaller->install($kit, $dependencyComponent, $this->tmpDir);
            }
        }

        foreach ($expectedFiles as $fileName => $expectedFile) {
            $this->assertFileExists($expectedFile);
            $this->assertSame($this->filesystem->readFile($expectedFile), $this->filesystem->readFile(\sprintf('%s/templates/components/%s', $kit->path, $fileName)));
        }
    }

    private function createKit(string $kitName): Kit
    {
        return self::getContainer()->get('ux_toolkit.kit.factory')->createKitFromAbsolutePath(Path::join(__DIR__, '../../kits', $kitName));
    }
}
