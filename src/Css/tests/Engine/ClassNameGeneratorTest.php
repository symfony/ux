<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Engine;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\ClassNameGenerator;
use Symfony\UX\Css\Exception\InvalidArgumentException;
use Symfony\UX\Css\Exception\UnsupportedStyleException;

final class ClassNameGeneratorTest extends TestCase
{
    private const CONFIG = [
        'utilities' => [
            'display' => ['className' => 'd'],
            'color' => ['className' => 'c'],
            'background' => ['className' => 'bg', 'shorthand' => 'bg'],
            'width' => ['className' => 'w', 'shorthand' => 'w'],
            'srOnly' => ['className' => 'sr', 'transform' => ['__function' => 'transform']],
            'opacity' => ['className' => 'op'],
            'zIndex' => ['className' => 'z'],
            'marginInline' => ['className' => 'mx', 'shorthand' => ['mx', 'marginX']],
        ],
        'conditions' => ['hover' => '&:is(:hover, [data-hover])', 'dark' => '[data-theme=dark] &'],
        'theme' => ['breakpoints' => ['sm' => '640px', 'md' => '768px', 'lg' => '1024px']],
    ];

    public static function providePandaRuntimeCases(): iterable
    {
        yield 'native property' => [['display' => 'flex'], 'd_flex'];
        yield 'token value' => [['color' => 'blue.300'], 'c_blue.300'];
        yield 'utility with a transform' => [['srOnly' => true], 'sr_true'];
        yield 'shorthand' => [['bg' => 'red'], 'bg_red'];
        yield 'condition in the value' => [['bg' => ['_hover' => 'yellow.100']], 'hover:bg_yellow.100'];
        yield 'condition as key' => [['_hover' => ['bg' => 'yellow.200']], 'hover:bg_yellow.200'];
        yield 'nested conditions' => [['_hover' => ['_dark' => ['bg' => 'pink']]], 'hover:dark:bg_pink'];
        yield 'arbitrary value' => [['color' => '#fff'], 'c_#fff'];
        yield 'arbitrary selector' => [['&:data-panda' => ['display' => 'flex']], '[&:data-panda]:d_flex'];
        yield 'breakpoint' => [['sm' => ['bg' => 'purple']], 'sm:bg_purple'];
        yield 'responsive array' => [['width' => ['50px', null, '60px']], 'w_50px md:w_60px'];
        yield 'important' => [['color' => 'red !important'], 'c_red!'];
        yield 'base styles go last' => [['base' => ['color' => 'red'], 'display' => 'flex'], 'd_flex c_red'];
        yield 'null ignored' => [['color' => null, 'display' => 'flex'], 'd_flex'];
        yield 'whitespace collapsed' => [['display' => 'inline  flex'], 'd_inline_flex'];
        yield 'shorthand overrides its property' => [['width' => '1px', 'w' => '2px'], 'w_2px'];
        yield 'conditional value' => [['color' => ['base' => 'red', 'md' => 'blue']], 'c_red md:c_blue'];
        yield 'breakpoint then condition' => [['md' => ['_hover' => ['color' => 'red']]], 'md:hover:c_red'];
        yield 'condition then breakpoint' => [['_hover' => ['md' => ['color' => 'red']]], 'hover:md:c_red'];
        yield 'parent selector' => [['[dir=rtl] &' => ['color' => 'red']], '[[dir=rtl]_&]:c_red'];
        yield 'arbitrary at-rule' => [['@media print' => ['color' => 'red']], '[@media_print]:c_red'];
        yield 'numbers' => [['opacity' => 0.5, 'zIndex' => 10], 'op_0.5 z_10'];
        yield 'bang inside a value' => [['content' => '"a!b"'], 'content_"ab"!'];
        yield 'unknown nesting' => [['foo' => ['bar' => 'x']], 'bar:foo_x'];
        yield 'negative number' => [['mx' => -2], 'mx_-2'];
    }

    #[DataProvider('providePandaRuntimeCases')]
    public function testGenerateMatchesPandaRuntime(array $styles, string $expected): void
    {
        $generator = ClassNameGenerator::fromPandaConfig(self::CONFIG);

        $this->assertSame($expected, $generator->generate($styles));
    }

    public static function provideJavaScriptValues(): iterable
    {
        yield 'double space' => [['display' => 'inline  flex'], 'd_inline_flex'];
        yield 'tab' => [['display' => "inline\tflex"], 'd_inline_flex'];
        yield 'new line' => [['display' => "\"a b\"\n  \"c d\""], 'd_"a_b"_"c_d"'];
        yield 'no-break space' => [['display' => "a\u{A0}b"], 'd_a_b'];
        yield 'byte order mark' => [['display' => "a\u{FEFF}b"], 'd_a_b'];
        yield 'next line is not whitespace in JavaScript' => [['display' => "a\u{85}b"], "d_a\u{85}b"];
        yield 'integer' => [['opacity' => 10], 'op_10'];
        yield 'float' => [['opacity' => 0.5], 'op_0.5'];
        yield 'float needing 17 digits' => [['opacity' => 0.1 + 0.2], 'op_0.30000000000000004'];
        yield 'integral float' => [['opacity' => 1.0], 'op_1'];
        yield 'negative zero' => [['opacity' => -0.0], 'op_0'];
        yield 'true' => [['opacity' => true], 'op_true'];
        yield 'false' => [['opacity' => false], 'op_false'];
        yield 'responsive list inside a condition' => [['_hover' => ['width' => ['10px', null, '30px']]], 'hover:w_10px hover:md:w_30px'];
        yield 'null below the top level' => [['_hover' => ['color' => null, 'width' => '1px'], 'display' => null], 'hover:w_1px'];
        yield 'nested conditions in both orders' => [['md' => ['_hover' => ['color' => 'red']], '_hover' => ['md' => ['color' => 'blue']]], 'md:hover:c_red hover:md:c_blue'];
    }

    #[DataProvider('provideJavaScriptValues')]
    public function testValuesAreWrittenLikePandaRuntime(array $styles, string $expected): void
    {
        $generator = ClassNameGenerator::fromPandaConfig(self::CONFIG);

        $this->assertSame($expected, $generator->generate($styles));
    }

    public static function provideRejectedValues(): iterable
    {
        yield 'exponent' => [['opacity' => 1e21], UnsupportedStyleException::class];
        yield 'infinity' => [['opacity' => \INF], UnsupportedStyleException::class];
        yield 'object' => [['display' => new \stdClass()], UnsupportedStyleException::class];
        yield 'invalid UTF-8 with whitespace' => [['color' => "a \xFF b"], InvalidArgumentException::class];
        yield 'invalid UTF-8 with a bang' => [['color' => "a\xFF!"], InvalidArgumentException::class];
    }

    #[DataProvider('provideRejectedValues')]
    public function testInvalidValuesAreRejectedWithAnException(array $styles, string $exception): void
    {
        $generator = ClassNameGenerator::fromPandaConfig(self::CONFIG);

        $this->expectException($exception);

        $generator->generate($styles);
    }

    public function testSeparatorIsConfigurable(): void
    {
        $generator = ClassNameGenerator::fromPandaConfig(['separator' => '-'] + self::CONFIG);

        $this->assertSame('d-flex', $generator->generate(['display' => 'flex']));
    }

    public function testPrefixIsAddedToTheClassNameButNotToTheConditions(): void
    {
        $generator = ClassNameGenerator::fromPandaConfig(['prefix' => 'pd'] + self::CONFIG);

        $classNames = $generator->generate(['display' => 'flex', '_hover' => ['color' => 'red']]);

        $this->assertSame('pd-d_flex hover:pd-c_red', $classNames);
    }

    public function testTablesCanBeExportedAndReloaded(): void
    {
        $tables = ClassNameGenerator::fromPandaConfig(['prefix' => 'pd'] + self::CONFIG)->toArray();
        $reloaded = new ClassNameGenerator(...eval('return '.var_export($tables, true).';'));

        $classNames = $reloaded->generate(['display' => 'flex', '_hover' => ['color' => 'red'], 'p' => ['md' => 4]]);

        $this->assertSame('pd-d_flex hover:pd-c_red md:pd-p_4', $classNames);
    }

    public function testHashedClassNamesAreNotSupported(): void
    {
        $this->expectException(UnsupportedStyleException::class);

        ClassNameGenerator::fromPandaConfig(['hash' => true] + self::CONFIG);
    }
}
