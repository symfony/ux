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
use Symfony\UX\Css\Engine\Breakpoints;
use Symfony\UX\Css\Engine\Conditions;
use Symfony\UX\Css\Engine\StyleEncoder;
use Symfony\UX\Css\Engine\StyleEntry;
use Symfony\UX\Css\Engine\Utilities;
use Symfony\UX\Css\Exception\UnsupportedStyleException;

final class StyleEncoderTest extends TestCase
{
    public static function providePandaEncoderCases(): iterable
    {
        yield 'flat' => [['display' => 'flex', 'whiteSpace' => 'nowrap'], [['display', 'flex', []], ['whiteSpace', 'nowrap', []]]];
        yield 'shorthand overrides its property' => [['width' => '50px', 'w' => '20px'], [['width', '20px', []]]];
        yield 'conditional value' => [['color' => ['base' => 'red', 'md' => 'blue']], [['color', 'red', []], ['color', 'blue', ['md']]]];
        yield 'nested conditions' => [['_hover' => ['color' => 'red', '_dark' => ['bg' => 'blue']]], [['color', 'red', ['_hover']], ['background', 'blue', ['_hover', '_dark']]]];
        yield 'breakpoint then conditions' => [['top' => ['sm' => ['_rtl' => '20px', '_hover' => '50px'], 'lg' => '120px']], [['top', '20px', ['sm', '_rtl']], ['top', '50px', ['sm', '_hover']], ['top', '120px', ['lg']]]];
        yield 'conditions then breakpoint' => [['ml' => ['_ltr' => ['sm' => '4'], '_rtl' => '-4']], [['marginLeft', '4', ['_ltr', 'sm']], ['marginLeft', '-4', ['_rtl']]]];
        yield 'responsive array' => [['width' => ['50px', null, '60px']], [['width', '50px', []], ['width', '60px', ['md']]]];
        yield 'null ignored' => [['color' => null, 'display' => 'flex'], [['display', 'flex', []]]];
        yield 'url ignored' => [['backgroundImage' => 'https://example.com/a.png', 'color' => 'red'], [['color', 'red', []]]];
        yield 'arbitrary selector and nested conditions' => [['& > p' => ['color' => 'red'], 'md' => ['_hover' => ['color' => 'blue']]], [['color', 'red', ['& > p']], ['color', 'blue', ['md', '_hover']]]];
        yield 'unknown nesting keeps the leaf property' => [['foo' => ['bar' => 'x']], [['bar', 'x', []]]];
    }

    /**
     * @param list<array{string, string, list<string>}> $expected
     */
    #[DataProvider('providePandaEncoderCases')]
    public function testEncodeMatchesPandaEncoder(array $styles, array $expected): void
    {
        $entries = self::encoder()->encode($styles);
        $encoded = array_map(
            static fn (StyleEntry $entry): array => [$entry->property, $entry->value, $entry->conditions],
            $entries,
        );

        $this->assertSame($expected, $encoded);
    }

    public function testIdenticalEntriesAreKeptOnce(): void
    {
        $styles = ['color' => ['base' => 'red', '_hover' => 'blue'], '_hover' => ['color' => 'blue']];

        $entries = self::encoder()->encode($styles);

        $this->assertCount(2, $entries);
    }

    public function testObjectsAreRejected(): void
    {
        $encoder = self::encoder();

        $this->expectException(UnsupportedStyleException::class);

        $encoder->encode(['display' => new \stdClass()]);
    }

    private static function encoder(): StyleEncoder
    {
        $utilities = new Utilities([
            'width' => ['className' => 'w', 'shorthand' => 'w'],
            'background' => ['className' => 'bg', 'shorthand' => 'bg'],
            'marginLeft' => ['className' => 'ml', 'shorthand' => 'ml'],
        ]);
        $conditions = new Conditions(
            [
                'hover' => '&:is(:hover, [data-hover])',
                'dark' => '.dark &',
                'rtl' => '[dir=rtl] &',
                'ltr' => '[dir=ltr] &',
            ],
            new Breakpoints(['sm' => '640px', 'md' => '768px', 'lg' => '1024px']),
        );

        return new StyleEncoder($utilities, $conditions);
    }
}
