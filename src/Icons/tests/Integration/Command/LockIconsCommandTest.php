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
final class LockIconsCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

    private const ICONS = [
        __DIR__.'/../../Fixtures/icons/iconamoon/3d-duotone.svg',
        __DIR__.'/../../Fixtures/icons/flag/eu-4x3.svg',
    ];

    /**
     * @before
     *
     * @after
     */
    public static function cleanup(): void
    {
        $fs = new Filesystem();

        foreach (self::ICONS as $icon) {
            $fs->remove($icon);
        }
    }

    public function testImportFoundIcons(): void
    {
        foreach (self::ICONS as $icon) {
            $this->assertFileDoesNotExist($icon);
        }

        $this->executeConsoleCommand('ux:icons:lock')
            ->assertSuccessful()
            ->assertOutputContains('Scanning project for icons...')
            ->assertOutputContains('Imported flag:eu-4x3')
            ->assertOutputContains('Imported iconamoon:3d-duotone')
            ->assertOutputContains('Imported 2 icons')
        ;

        foreach (self::ICONS as $icon) {
            $this->assertFileExists($icon);
        }

        $this->executeConsoleCommand('ux:icons:lock')
            ->assertSuccessful()
            ->assertOutputContains('Imported 0 icons')
        ;
    }

    public function testForceImportFoundIcons(): void
    {
        $this->executeConsoleCommand('ux:icons:lock')
            ->assertSuccessful()
            ->assertOutputContains('Scanning project for icons...')
            ->assertOutputContains('Imported flag:eu-4x3')
            ->assertOutputContains('Imported iconamoon:3d-duotone')
            ->assertOutputContains('Imported 2 icons')
        ;

        $this->executeConsoleCommand('ux:icons:lock --force')
            ->assertSuccessful()
            ->assertOutputContains('Scanning project for icons...')
            ->assertOutputContains('Imported flag:eu-4x3')
            ->assertOutputContains('Imported iconamoon:3d-duotone')
            ->assertOutputContains('Imported 2 icons')
        ;
    }
}
