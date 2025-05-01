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
use Zenstruck\Console\Test\InteractsWithConsole;

class InstallComponentCommandTest extends KernelTestCase
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

    public function testShouldAbleToInstallComponentTableAndItsDependencies(): void
    {
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

        $testCommand = $this->consoleCommand('ux:toolkit:install-component Table --destination='.$this->tmpDir)
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('Installing component Table...')
            ->assertOutputContains('[OK] The component has been installed.')
        ;

        // Files should be created
        foreach ($expectedFiles as $fileName => $expectedFile) {
            $testCommand->assertOutputContains($fileName);
            $this->assertFileExists($expectedFile);
            $this->assertEquals(file_get_contents(__DIR__.'/../../kits/shadcn/templates/components/'.$fileName), file_get_contents($expectedFile));
        }
    }

    public function testShouldFailAndSuggestAlternativeComponents(): void
    {
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid();
        mkdir($destination);

        $this->bootKernel();
        $this->consoleCommand('ux:toolkit:install-component Table: --destination='.$destination)
            ->execute()
            ->assertFaulty()
            ->assertOutputContains('[WARNING] The component "Table:" does not exist.')
            ->assertOutputContains('Possible alternatives: ')
            ->assertOutputContains('"Table:Body"')
            ->assertOutputContains('"Table:Caption"')
            ->assertOutputContains('"Table:Cell"')
            ->assertOutputContains('"Table:Footer"')
            ->assertOutputContains('"Table:Head"')
            ->assertOutputContains('"Table:Header"')
            ->assertOutputContains('"Table:Row"')
        ;
    }

    public function testShouldFailWhenComponentDoesNotExist(): void
    {
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid();
        mkdir($destination);

        $this->bootKernel();
        $this->consoleCommand('ux:toolkit:install-component Unknown --destination='.$destination)
            ->execute()
            ->assertFaulty()
            ->assertOutputContains('The component "Unknown" does not exist.');
    }

    public function testShouldWarnWhenComponentFileAlreadyExistsInNonInteractiveMode(): void
    {
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid();
        mkdir($destination);

        $this->bootKernel();
        $this->consoleCommand('ux:toolkit:install-component Badge --destination='.$destination)
            ->execute()
            ->assertSuccessful();

        $this->consoleCommand('ux:toolkit:install-component Badge --destination='.$destination)
            ->execute()
            ->assertFaulty()
            ->assertOutputContains('[WARNING] The component has not been installed.')
        ;
    }
}
