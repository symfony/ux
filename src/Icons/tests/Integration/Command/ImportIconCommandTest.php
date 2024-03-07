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
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Console\Test\InteractsWithConsole;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ImportIconCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

    private const ICON_DIR = __DIR__.'/../../Fixtures/icons';
    private const ICONS = ['uiw/dashboard.svg'];

    /**
     * @before
     *
     * @after
     */
    public static function cleanup(): void
    {
        $fs = new Filesystem();

        foreach (self::ICONS as $icon) {
            $fs->remove(self::ICON_DIR.'/'.$icon);
        }
    }

    public function testCanImportIcon(): void
    {
        $this->assertFileDoesNotExist($expectedFile = self::ICON_DIR.'/uiw/dashboard.svg');

        $this->executeConsoleCommand('ux:icons:import uiw:dashboard')
            ->assertSuccessful()
            ->assertOutputContains('Importing uiw:dashboard')
            ->assertOutputContains("Imported uiw:dashboard (License: MIT), render with {{ ux_icon('uiw:dashboard') }}")
        ;

        $this->assertFileExists($expectedFile);
    }

    public function testImportInvalidIconName(): void
    {
        $this->executeConsoleCommand('ux:icons:import something')
            ->assertStatusCode(1)
            ->assertOutputContains('[ERROR] Invalid icon name "something".')
        ;
    }

    public function testImportNonExistentIcon(): void
    {
        $this->executeConsoleCommand('ux:icons:import something:invalid')
            ->assertStatusCode(1)
            ->assertOutputContains('[ERROR] The icon "something:invalid" does not exist on iconify.design.')
        ;

        $this->assertFileDoesNotExist(self::ICON_DIR.'/invalid.svg');
    }
}
