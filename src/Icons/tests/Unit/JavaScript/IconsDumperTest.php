<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\JavaScript;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Icons\IconFactory;
use Symfony\UX\Icons\IconRenderer;
use Symfony\UX\Icons\JavaScript\IconsDumper;
use Symfony\UX\Icons\Registry\LocalSvgIconRegistry;

final class IconsDumperTest extends TestCase
{
    private const FIXTURES = __DIR__.'/../../Fixtures';

    private string $dumpDir;

    protected function setUp(): void
    {
        $this->dumpDir = sys_get_temp_dir().'/ux_icons_dumper_'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->dumpDir);
    }

    public function testDumpLocalIconsWithResolvedAttributes(): void
    {
        $dumper = $this->createDumper(
            defaultAttributes: ['fill' => 'currentColor', 'class' => 'icon'],
            setAttributes: ['img' => ['class' => 'icon icon-img']],
            iconSetPaths: ['img' => self::FIXTURES.'/images'],
            aliases: ['me' => 'user', 'remote' => 'lucide:circle'],
        );

        $names = $dumper->dump();

        $this->assertSame(['a:icon', 'img:a:b:icon', 'img:a:icon', 'img:ab:icon', 'img:icon', 'me', 'sub:check', 'user'], $names);
        $this->assertNotContains('sub_dir:check', $names, 'Invalid icon names are skipped.');
        $this->assertNotContains('remote', $names, 'Aliases targeting a missing icon are skipped.');

        $js = file_get_contents($this->dumpDir.'/index.js');
        $this->assertStringContainsString('export const UserIcon = {"name":"user","attributes":{"xmlns":"http://www.w3.org/2000/svg","viewBox":"0 0 24 24","fill":"currentColor","class":"icon"},"children":[["path",{"fill-rule":"evenodd","d":"M7.5', $js);
        $this->assertStringContainsString('export const ImgIconIcon = {"name":"img:icon","attributes":{', $js);
        $this->assertStringContainsString('"class":"icon icon-img"', $js);
        $this->assertStringNotContainsString('aria-hidden', $js, 'aria-hidden depends on invocation attributes, it is resolved in JavaScript.');

        $dts = file_get_contents($this->dumpDir.'/index.d.ts');
        $this->assertStringContainsString("import type { UXIconData } from '@symfony/ux-icons';", $dts);
        $this->assertStringContainsString('export declare const SubCheckIcon: UXIconData<"sub:check">;', $dts);
    }

    public function testDumpWithoutTypeScript(): void
    {
        new Filesystem()->dumpFile($this->dumpDir.'/index.d.ts', 'stale');

        $this->createDumper(dumpTypeScript: false)->dump();

        $this->assertFileExists($this->dumpDir.'/index.js');
        $this->assertFileDoesNotExist($this->dumpDir.'/index.d.ts');
    }

    public function testDumpThrowsOnExportNameCollision(): void
    {
        $dumper = $this->createDumper(aliases: ['sub-check' => 'user']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Icons "sub:check" and "sub-check" would both be exported as "SubCheckIcon"');

        $dumper->dump();
    }

    /**
     * @param list<mixed> $expected
     */
    #[DataProvider('provideChildren')]
    public function testChildren(string $innerSvg, array $expected): void
    {
        $this->assertSame(json_encode($expected), json_encode(IconsDumper::children($innerSvg)));
    }

    public static function provideChildren(): iterable
    {
        yield 'nested elements' => [
            '<g fill="none"><path d="M0 0"></path></g>',
            [['g', ['fill' => 'none'], [['path', ['d' => 'M0 0']]]]],
        ];
        yield 'elements without attributes are objects' => [
            '<path></path>',
            [['path', new \stdClass()]],
        ];
        yield 'SVG case and namespaced attributes' => [
            '<defs><linearGradient id="a"></linearGradient></defs><use xlink:href="#a"></use>',
            [['defs', new \stdClass(), [['linearGradient', ['id' => 'a']]]], ['use', ['xlink:href' => '#a']]],
        ];
        yield 'texts are decoded, style is raw' => [
            '<style>a > b {}</style><text>a &amp; b &lt;c&gt;<tspan>d</tspan></text>',
            [['style', new \stdClass(), ['a > b {}']], ['text', new \stdClass(), ['a & b <c>', ['tspan', new \stdClass(), ['d']]]]],
        ];
    }

    #[DataProvider('provideExportNames')]
    public function testExportName(string $name, string $expected): void
    {
        $this->assertSame($expected, IconsDumper::exportName($name));
    }

    public static function provideExportNames(): iterable
    {
        yield ['user', 'UserIcon'];
        yield ['lucide:trash-2', 'LucideTrash2Icon'];
        yield ['flags:fr', 'FlagsFrIcon'];
        yield ['500', '_500Icon'];
        yield ['delete', 'DeleteIcon'];
    }

    /**
     * @param array<string, string>                $defaultAttributes
     * @param array<string, array<string, string>> $setAttributes
     * @param array<string, string>                $iconSetPaths
     * @param array<string, string>                $aliases
     */
    private function createDumper(array $defaultAttributes = [], array $setAttributes = [], array $iconSetPaths = [], array $aliases = [], bool $dumpTypeScript = true): IconsDumper
    {
        $registry = new LocalSvgIconRegistry(new IconFactory(), self::FIXTURES.'/icons', $iconSetPaths);
        $renderer = new IconRenderer($registry, $defaultAttributes, $aliases, $setAttributes);

        return new IconsDumper($renderer, self::FIXTURES.'/icons', $iconSetPaths, $aliases, $this->dumpDir, $dumpTypeScript);
    }
}
