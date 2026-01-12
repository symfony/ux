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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Zenstruck\Console\Test\InteractsWithConsole;

class InstallCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

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

    public function testShouldAbleToInstallComponentTableAndItsDependencies()
    {
        $expectedFiles = [
            'table/templates/components/Table.html.twig' => Path::normalize($this->tmpDir.'/templates/components/Table.html.twig'),
            'table/templates/components/Table/Body.html.twig' => Path::normalize($this->tmpDir.'/templates/components/Table/Body.html.twig'),
            'table/templates/components/Table/Caption.html.twig' => Path::normalize($this->tmpDir.'/templates/components/Table/Caption.html.twig'),
            'table/templates/components/Table/Cell.html.twig' => Path::normalize($this->tmpDir.'/templates/components/Table/Cell.html.twig'),
            'table/templates/components/Table/Footer.html.twig' => Path::normalize($this->tmpDir.'/templates/components/Table/Footer.html.twig'),
            'table/templates/components/Table/Head.html.twig' => Path::normalize($this->tmpDir.'/templates/components/Table/Head.html.twig'),
            'table/templates/components/Table/Header.html.twig' => Path::normalize($this->tmpDir.'/templates/components/Table/Header.html.twig'),
            'table/templates/components/Table/Row.html.twig' => Path::normalize($this->tmpDir.'/templates/components/Table/Row.html.twig'),
        ];

        foreach ($expectedFiles as $expectedFile) {
            $this->assertFileDoesNotExist($expectedFile);
        }

        $testCommand = $this->consoleCommand(\sprintf('ux:install table --destination="%s"', str_replace('\\', '\\\\', $this->tmpDir)))
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('Installing recipe "table" from the Shadcn UI kit...')
            ->assertOutputContains('[OK] The recipe has been installed.')
        ;

        // Files should be created
        foreach ($expectedFiles as $fileName => $expectedFile) {
            $testCommand->assertOutputContains($expectedFile);
            $this->assertFileExists($expectedFile);
            $this->assertEquals(file_get_contents(__DIR__.'/../../kits/shadcn/'.$fileName), file_get_contents($expectedFile));
        }
    }

    public function testShouldFailAndSuggestAlternativeRecipesWhenKitIsExplicit()
    {
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid();
        mkdir($destination);

        $this->bootKernel();
        $this->consoleCommand('ux:install a --kit=shadcn --destination='.$destination)
            ->execute()
            ->assertFaulty()
            ->assertOutputContains('[WARNING] The recipe "a" does not exist')
            ->assertOutputContains('Possible alternatives: "accordion", "alert", "alert-dialog"')
        ;
    }

    public function testShouldFailWhenComponentDoesNotExist()
    {
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid();
        mkdir($destination);

        $this->bootKernel();
        $this->consoleCommand('ux:install unknown --destination='.$destination)
            ->execute()
            ->assertFaulty()
            ->assertOutputContains('The recipe "unknown" does not exist');
    }

    public function testShouldWarnWhenComponentFileAlreadyExistsInNonInteractiveMode()
    {
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid();
        mkdir($destination);

        $this->bootKernel();
        $this->consoleCommand('ux:install badge --destination='.$destination)
            ->execute()
            ->assertSuccessful();

        $this->consoleCommand('ux:install badge --destination='.$destination)
            ->execute()
            ->assertFaulty()
            ->assertOutputContains('[WARNING] The recipe has not been installed.')
        ;
    }
}
