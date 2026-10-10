<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Router\JavaScriptRegexConverter;

final class JavaScriptRegexConverterTest extends TestCase
{
    #[DataProvider('provideRegexes')]
    public function testConvert(string $regex, ?string $expected, bool $utf8 = false): void
    {
        self::assertSame($expected, JavaScriptRegexConverter::convert($regex, $utf8));
    }

    public static function provideRegexes(): iterable
    {
        yield 'default requirement' => ['[^/]++', '[^/]+'];
        yield 'default requirement with next separator' => ['[^/\.]++', '[^/\.]+'];
        yield 'greedy quantifier' => ['\d+', '\d+'];
        yield 'possessive star' => ['a*+b', 'a*b'];
        yield 'possessive optional' => ['a?+', 'a?'];
        yield 'possessive interval' => ['a{2,3}+', 'a{2,3}'];
        yield 'lazy quantifier' => ['a+?', 'a+?'];
        yield 'possessive group' => ['(?:ab)++', '(?:ab)+'];
        yield 'escaped plus then greedy plus' => ['\++', '\++'];
        yield 'escaped plus then possessive plus' => ['\+++', '\++'];
        yield 'plus inside a character class' => ['[+]+', '[+]+'];
        yield 'alternation' => ['en|fr', 'en|fr'];
        yield 'negative lookahead is stripped' => ['(?!new)\w+', '\w+'];
        yield 'lookbehind is stripped' => ['(?<=a)b', 'b'];
        yield 'lookahead with a nested group is stripped' => ['(?=(a|b))\w+', '\w+'];
        yield 'named group' => ['(?<year>\d{4})', '(?<year>\d{4})'];
        yield 'leading bracket in a class' => ['[]a]+', '[\]a]+'];
        yield 'leading bracket in a negated class' => ['[^]a]', '[^\]a]'];
        yield 'escapes in a class' => ['[\w\-]+', '[\w\-]+'];
        yield 'hex escape' => ['\x41', '\x41'];
        yield 'word class without utf8' => ['\w+', '\w+'];
        yield 'word class with utf8' => ['\w+', '[\p{L}\p{N}\p{Mn}\p{Pc}]+', true];
        yield 'word class in a class with utf8' => ['[\w-]+', '[\p{L}\p{N}\p{Mn}\p{Pc}-]+', true];
        yield 'non-word class with utf8' => ['\W', '[^\p{L}\p{N}\p{Mn}\p{Pc}]', true];
        yield 'non-word class in a class with utf8' => ['[\W]', null, true];
        yield 'digit with utf8' => ['\d+', '\p{Nd}+', true];
        yield 'non-digit with utf8' => ['\D', '\P{Nd}', true];
        yield 'non-digit in a class with utf8' => ['[\D]', '[\P{Nd}]', true];
        yield 'word boundary with utf8' => ['\b\w+\b', null, true];
        yield 'non-word boundary with utf8' => ['a\Bb', null, true];
        yield 'unicode property with utf8' => ['\p{L}+', '\p{L}+', true];
        yield 'unicode property without utf8' => ['\p{L}+', null];
        yield 'short unicode property' => ['\pL+', null, true];
        yield 'start of subject anchor' => ['\Aabc', null];
        yield 'end of subject anchor' => ['abc\z', null];
        yield 'horizontal whitespace' => ['\h+', null];
        yield 'vertical whitespace' => ['\v+', null];
        yield 'braced hex escape' => ['\x{263A}', null];
        yield 'posix class' => ['[[:alpha:]]+', null];
        yield 'inline flag' => ['(?i)abc', null];
        yield 'atomic group' => ['(?>ab)', null];
        yield 'python named group' => ['(?P<id>\d+)', null];
        yield 'unterminated class' => ['[abc', null];
        yield 'trailing backslash' => ['abc\\', null];
    }
}
