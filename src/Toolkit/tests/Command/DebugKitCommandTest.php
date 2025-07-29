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
use Symfony\Component\Filesystem\Path;
use Zenstruck\Console\Test\InteractsWithConsole;

class DebugKitCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

    public function testShouldBeAbleToDebug()
    {
        $this->bootKernel();
        $this->consoleCommand(\sprintf('ux:toolkit:debug-kit %s', Path::join(__DIR__, '/../../kits/shadcn')))
            ->execute()
            ->assertSuccessful()
            // Kit details
            ->assertOutputContains('Name       Shadcn')
            ->assertOutputContains('Homepage   https://ux.symfony.com/components')
            ->assertOutputContains('License    MIT')
            // Components details
            ->assertOutputContains(implode(\PHP_EOL, [
                '+--------------+----------------------- Component: "Avatar" --------------------------------------+',
                '| File(s)      | templates/components/Avatar.html.twig                                            |',
                '| Dependencies | tales-from-a-dev/twig-tailwind-extra                                             |',
                '|              | Avatar:Image                                                                     |',
                '|              | Avatar:Text                                                                      |',
                '+--------------+----------------------------------------------------------------------------------+',
            ]))
            ->assertOutputContains(implode(\PHP_EOL, [
                '+--------------+----------------------- Component: "Table" ---------------------------------------+',
                '| File(s)      | templates/components/Table.html.twig                                             |',
                '| Dependencies | tales-from-a-dev/twig-tailwind-extra                                             |',
                '|              | Table:Body                                                                       |',
                '|              | Table:Caption                                                                    |',
                '|              | Table:Cell                                                                       |',
                '|              | Table:Footer                                                                     |',
                '|              | Table:Head                                                                       |',
                '|              | Table:Header                                                                     |',
                '|              | Table:Row                                                                        |',
                '+--------------+----------------------------------------------------------------------------------+',
            ]));
    }
}
