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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Console\Test\InteractsWithConsole;

/**
 * @author Jean-François Lépine
 */
class DebugUxToolkitCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

    public function testShouldBeAbleToListComponents(): void
    {
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid();
        mkdir($destination);

        $this->bootKernel();
        $this->consoleCommand('debug:ux:toolkit')
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('Current theme:')
            ->assertOutputContains('Available components:')
            ->assertOutputContains('Badge')
            ->assertOutputContains('Button')
        ;
    }
}
