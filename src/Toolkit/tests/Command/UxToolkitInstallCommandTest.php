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
use Zenstruck\Console\Test\InteractsWithConsole;

/**
 * @author Jean-François Lépine
 */
class UxToolkitInstallCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

    public function testShouldAbleToCreateTheBadgeComponent(): void
    {
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid();
        mkdir($destination);

        $this->bootKernel();
        $this->consoleCommand('ux:toolkit:install badge --destination='.$destination)
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('component "Badge" has been installed');

        // A file should be created
        $expectedFile = $destination.\DIRECTORY_SEPARATOR.'Badge.html.twig';
        $this->assertFileExists($expectedFile);

        // The content of the file should be the same as the content of the Badge component
        $expectedContent = file_get_contents(__DIR__.'/../../templates/default/components/Badge.html.twig');
        $actualContent = file_get_contents($expectedFile);
        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testShouldFailWhenComponentDoesNotExist(): void
    {
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid();
        mkdir($destination);

        $this->bootKernel();
        $this->consoleCommand('ux:toolkit:install unknown --destination='.$destination)
            ->execute()
            ->assertFaulty()
            ->assertOutputContains('The component "Unknown" does not exist.');
    }

    public function testShouldFailWhenComponentFileAlreadyExistsInNonInteractiveMode(): void
    {
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid();
        mkdir($destination);

        $this->bootKernel();
        $this->consoleCommand('ux:toolkit:install badge --destination='.$destination)
            ->execute()
            ->assertSuccessful();

        $this->consoleCommand('ux:toolkit:install badge --destination='.$destination)
            ->execute()
            ->assertFaulty()
            ->assertOutputContains('The component "Badge" already exists.');
    }
}
