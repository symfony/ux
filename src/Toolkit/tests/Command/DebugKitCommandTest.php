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

class DebugKitCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

    public function testShouldBeAbleToDebug(): void
    {
        $this->bootKernel();
        $this->consoleCommand(\sprintf('ux:toolkit:debug-kit %s', __DIR__.'/../../kits/shadcn'))
            ->execute()
            ->assertSuccessful()
            // Kit details
            ->assertOutputContains('Name       Shadcn')
            ->assertOutputContains('Homepage   https://ux.symfony.com/components')
            ->assertOutputContains('License    MIT')
            // A component details
            ->assertOutputContains(<<<'EOF'
+--------------+----------------------- Component: "Avatar" --------------------------------------+
| File(s)      | templates/components/Avatar.html.twig (Twig)                                     |
| Dependencies | Avatar:Image                                                                     |
|              | Avatar:Text                                                                      |
|              | tales-from-a-dev/twig-tailwind-extra                                             |
+--------------+----------------------------------------------------------------------------------+
EOF
            );
    }
}
