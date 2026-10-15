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

final class DumpIconsCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

    public function testCanDumpIcons(): void
    {
        $this->executeConsoleCommand('ux:icons:dump -v')
            ->assertSuccessful()
            ->assertOutputContains('user')
            ->assertOutputContains('sub:check')
            ->assertOutputContains('Dumped')
        ;

        $js = file_get_contents(self::$kernel->getCacheDir().'/ux_icons_js/index.js');

        $this->assertStringContainsString('export const UserIcon = {"name":"user","attributes":{', $js);
        $this->assertStringContainsString('"fill":"currentColor"', $js);
        // "foo" is an alias of a remote icon that is not imported locally
        $this->assertStringNotContainsString('FooIcon', $js);
    }
}
