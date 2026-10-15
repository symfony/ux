<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\Finder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Icons\Finder\JavaScriptIconFinder;
use Symfony\UX\Icons\JavaScript\IconsDumper;

final class JavaScriptIconFinderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/ux_icons_js_finder_'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->directory);
    }

    public function testFindIconsImportedFromIconModules(): void
    {
        $fs = new Filesystem();
        $fs->dumpFile($this->directory.'/app.js', <<<'JS'
            import { LucideMailIcon, HeroiconsXMarkIcon as Close } from '../var/icons/index.js';
            import { Controller } from '@hotwired/stimulus';
            import { ArrowIcon } from './components/arrow.js';
            JS);
        $fs->dumpFile($this->directory.'/components/Toolbar.tsx', <<<'TSX'
            import type { UXIconData } from '@symfony/ux-icons';
            import {
                SimpleIconsGithubIcon,
                type TablerTrash2Icon,
            } from '@/icons';
            TSX);
        $fs->dumpFile($this->directory.'/node_modules/pkg/index.js', "import { IgnoredDependencyIcon } from '../var/icons/index.js';");

        $icons = new JavaScriptIconFinder([$this->directory, $this->directory.'/missing'])->icons();

        $this->assertContains('lucide:mail', $icons);
        $this->assertContains('heroicons:x-mark', $icons);
        $this->assertContains('simple-icons:github', $icons);
        $this->assertNotContains('tabler:trash-2', $icons, 'Type-only specifiers are not icons.');
        $this->assertNotContains('ignored:dependency', $icons, 'node_modules are not scanned.');
        $this->assertNotContains('close', $icons, 'Local aliases are ignored.');
        $this->assertSame([], array_filter($icons, static fn (string $name) => str_starts_with($name, 'controller')), 'Only icon modules are scanned.');
    }

    public function testFindNothingWithoutDirectories(): void
    {
        $this->assertSame([], new JavaScriptIconFinder([$this->directory])->icons());
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('provideIconNames')]
    public function testIconNames(string $exportName, array $expected): void
    {
        $names = JavaScriptIconFinder::iconNames($exportName);

        $this->assertSame($expected, $names);
        foreach ($names as $name) {
            $this->assertSame($exportName, IconsDumper::exportName($name), \sprintf('"%s" is dumped as "%s".', $name, $exportName));
        }
    }

    public static function provideIconNames(): iterable
    {
        yield ['LucideMailIcon', ['lucide:mail']];
        yield ['HeroiconsXMarkIcon', ['heroicons:x-mark', 'heroicons-x:mark']];
        yield ['SimpleIconsGithubIcon', ['simple:icons-github', 'simple-icons:github']];
        yield ['LucideTrash2Icon', ['lucide:trash-2', 'lucide:trash2', 'lucide-trash:2']];
        yield ['Mdi2xlIcon', ['mdi:2xl']];
        yield ['Ri24HoursFillIcon', ['ri:24-hours-fill', 'ri-24:hours-fill', 'ri24:hours-fill', 'ri-24-hours:fill', 'ri24-hours:fill']];
        yield ['_500Icon', []];
        yield ['UserIcon', []];
        yield ['Icon', []];
        yield ['UXIconData', []];
        yield ['useIcon', []];
    }
}
