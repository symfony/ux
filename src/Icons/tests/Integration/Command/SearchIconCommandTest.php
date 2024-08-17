<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Integration\Command;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Console\Test\InteractsWithConsole;

/**
 * @author Simon Andreé <smn.andre@gmail.com>
 */
final class SearchIconCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

    public function testSearchWithPrefix(): void
    {
        $this->consoleCommand('ux:icons:search iconoir')
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('Icon set')
            ->assertOutputContains('Iconoir')
            ->assertOutputContains('Icons')
            ->assertOutputContains('License')
            ->assertOutputContains('MIT')
            ->assertOutputContains('Prefix')
            ->assertOutputContains('iconoir')
            ->assertOutputContains('Example')
            ->assertOutputContains('iconoir:')
            ->assertOutputContains('php bin/console ux:icons:search iconoir')
            ->assertStatusCode(0);
    }

    public function testSearchWithPrefixMatchingMultipleSet(): void
    {
        $this->consoleCommand('ux:icons:search box')
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('BoxIcons')
            ->assertOutputContains('bx ')
            ->assertOutputContains('BoxIcons Solid')
            ->assertOutputContains('bxs ')
            ->assertOutputContains('BoxIcons Logo')
            ->assertOutputContains('bxl ')
            ->assertStatusCode(0);
    }

    public function testSearchWithPrefixName(): void
    {
        $this->consoleCommand('ux:icons:search lucide arrow')
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('Searching Lucide icons "arrow"...')
            ->assertOutputContains('lucide:arrow-')
            ->assertOutputContains('lucide:circle-arrow')
            ->assertOutputContains('See all the lucide icons')
            ->assertOutputContains('https://ux.symfony.com/icons?set=lucide')
            ->assertStatusCode(0);
    }
}
