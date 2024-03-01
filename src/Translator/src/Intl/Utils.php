<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Intl;

use function Symfony\Component\String\s;

/**
 * @experimental
 */
final class Utils
{
    private function __construct()
    {
    }

    /**
     * This check if codepoint is alphabet (lower & uppercase).
     */
    public static function isAlpha(int $codepoint): bool
    {
        return
            ($codepoint >= 97 && $codepoint <= 122)
            || ($codepoint >= 65 && $codepoint <= 90)
        ;
    }

    public static function isAlphaOrSlash(int $codepoint): bool
    {
        return self::isAlpha($codepoint) || 47 === $codepoint; /* '/' */
    }

    /** See `parseTag` function docs. */
    public static function isPotentialElementNameChar(int $c): bool
    {
        return
            45 === $c /* '-' */
            || 46 === $c /* '.' */
            || ($c >= 48 && $c <= 57) /* 0..9 */
            || 95 === $c /* '_' */
            || ($c >= 97 && $c <= 122) /* a..z */
            || ($c >= 65 && $c <= 90) /* A..Z */
            || 0xB7 == $c
            || ($c >= 0xC0 && $c <= 0xD6)
            || ($c >= 0xD8 && $c <= 0xF6)
            || ($c >= 0xF8 && $c <= 0x37D)
            || ($c >= 0x37F && $c <= 0x1FFF)
            || ($c >= 0x200C && $c <= 0x200D)
            || ($c >= 0x203F && $c <= 0x2040)
            || ($c >= 0x2070 && $c <= 0x218F)
            || ($c >= 0x2C00 && $c <= 0x2FEF)
            || ($c >= 0x3001 && $c <= 0xD7FF)
            || ($c >= 0xF900 && $c <= 0xFDCF)
            || ($c >= 0xFDF0 && $c <= 0xFFFD)
            || ($c >= 0x10000 && $c <= 0xEFFFF)
        ;
    }

    /**
     * Code point equivalent of regex `\p{White_Space}`.
     * From: https://www.unicode.org/Public/UCD/latest/ucd/PropList.txt.
     */
    public static function isWhiteSpace(int $c)
    {
        return
            ($c >= 0x0009 && $c <= 0x000D)
            || 0x0020 === $c
            || 0x0085 === $c
            || ($c >= 0x200E && $c <= 0x200F)
            || 0x2028 === $c
            || 0x2029 === $c
        ;
    }

    /**
     * Code point equivalent of regex `\p{Pattern_Syntax}`.
     * See https://www.unicode.org/Public/UCD/latest/ucd/PropList.txt.
     */
    public static function isPatternSyntax(int $c): bool
    {
        return
            ($c >= 0x0021 && $c <= 0x0023)
            || 0x0024 === $c
            || ($c >= 0x0025 && $c <= 0x0027)
            || 0x0028 === $c
            || 0x0029 === $c
            || 0x002A === $c
            || 0x002B === $c
            || 0x002C === $c
            || 0x002D === $c
            || ($c >= 0x002E && $c <= 0x002F)
            || ($c >= 0x003A && $c <= 0x003B)
            || ($c >= 0x003C && $c <= 0x003E)
            || ($c >= 0x003F && $c <= 0x0040)
            || 0x005B === $c
            || 0x005C === $c
            || 0x005D === $c
            || 0x005E === $c
            || 0x0060 === $c
            || 0x007B === $c
            || 0x007C === $c
            || 0x007D === $c
            || 0x007E === $c
            || 0x00A1 === $c
            || ($c >= 0x00A2 && $c <= 0x00A5)
            || 0x00A6 === $c
            || 0x00A7 === $c
            || 0x00A9 === $c
            || 0x00AB === $c
            || 0x00AC === $c
            || 0x00AE === $c
            || 0x00B0 === $c
            || 0x00B1 === $c
            || 0x00B6 === $c
            || 0x00BB === $c
            || 0x00BF === $c
            || 0x00D7 === $c
            || 0x00F7 === $c
            || ($c >= 0x2010 && $c <= 0x2015)
            || ($c >= 0x2016 && $c <= 0x2017)
            || 0x2018 === $c
            || 0x2019 === $c
            || 0x201A === $c
            || ($c >= 0x201B && $c <= 0x201C)
            || 0x201D === $c
            || 0x201E === $c
            || 0x201F === $c
            || ($c >= 0x2020 && $c <= 0x2027)
            || ($c >= 0x2030 && $c <= 0x2038)
            || 0x2039 === $c
            || 0x203A === $c
            || ($c >= 0x203B && $c <= 0x203E)
            || ($c >= 0x2041 && $c <= 0x2043)
            || 0x2044 === $c
            || 0x2045 === $c
            || 0x2046 === $c
            || ($c >= 0x2047 && $c <= 0x2051)
            || 0x2052 === $c
            || 0x2053 === $c
            || ($c >= 0x2055 && $c <= 0x205E)
            || ($c >= 0x2190 && $c <= 0x2194)
            || ($c >= 0x2195 && $c <= 0x2199)
            || ($c >= 0x219A && $c <= 0x219B)
            || ($c >= 0x219C && $c <= 0x219F)
            || 0x21A0 === $c
            || ($c >= 0x21A1 && $c <= 0x21A2)
            || 0x21A3 === $c
            || ($c >= 0x21A4 && $c <= 0x21A5)
            || 0x21A6 === $c
            || ($c >= 0x21A7 && $c <= 0x21AD)
            || 0x21AE === $c
            || ($c >= 0x21AF && $c <= 0x21CD)
            || ($c >= 0x21CE && $c <= 0x21CF)
            || ($c >= 0x21D0 && $c <= 0x21D1)
            || 0x21D2 === $c
            || 0x21D3 === $c
            || 0x21D4 === $c
            || ($c >= 0x21D5 && $c <= 0x21F3)
            || ($c >= 0x21F4 && $c <= 0x22FF)
            || ($c >= 0x2300 && $c <= 0x2307)
            || 0x2308 === $c
            || 0x2309 === $c
            || 0x230A === $c
            || 0x230B === $c
            || ($c >= 0x230C && $c <= 0x231F)
            || ($c >= 0x2320 && $c <= 0x2321)
            || ($c >= 0x2322 && $c <= 0x2328)
            || 0x2329 === $c
            || 0x232A === $c
            || ($c >= 0x232B && $c <= 0x237B)
            || 0x237C === $c
            || ($c >= 0x237D && $c <= 0x239A)
            || ($c >= 0x239B && $c <= 0x23B3)
            || ($c >= 0x23B4 && $c <= 0x23DB)
            || ($c >= 0x23DC && $c <= 0x23E1)
            || ($c >= 0x23E2 && $c <= 0x2426)
            || ($c >= 0x2427 && $c <= 0x243F)
            || ($c >= 0x2440 && $c <= 0x244A)
            || ($c >= 0x244B && $c <= 0x245F)
            || ($c >= 0x2500 && $c <= 0x25B6)
            || 0x25B7 === $c
            || ($c >= 0x25B8 && $c <= 0x25C0)
            || 0x25C1 === $c
            || ($c >= 0x25C2 && $c <= 0x25F7)
            || ($c >= 0x25F8 && $c <= 0x25FF)
            || ($c >= 0x2600 && $c <= 0x266E)
            || 0x266F === $c
            || ($c >= 0x2670 && $c <= 0x2767)
            || 0x2768 === $c
            || 0x2769 === $c
            || 0x276A === $c
            || 0x276B === $c
            || 0x276C === $c
            || 0x276D === $c
            || 0x276E === $c
            || 0x276F === $c
            || 0x2770 === $c
            || 0x2771 === $c
            || 0x2772 === $c
            || 0x2773 === $c
            || 0x2774 === $c
            || 0x2775 === $c
            || ($c >= 0x2794 && $c <= 0x27BF)
            || ($c >= 0x27C0 && $c <= 0x27C4)
            || 0x27C5 === $c
            || 0x27C6 === $c
            || ($c >= 0x27C7 && $c <= 0x27E5)
            || 0x27E6 === $c
            || 0x27E7 === $c
            || 0x27E8 === $c
            || 0x27E9 === $c
            || 0x27EA === $c
            || 0x27EB === $c
            || 0x27EC === $c
            || 0x27ED === $c
            || 0x27EE === $c
            || 0x27EF === $c
            || ($c >= 0x27F0 && $c <= 0x27FF)
            || ($c >= 0x2800 && $c <= 0x28FF)
            || ($c >= 0x2900 && $c <= 0x2982)
            || 0x2983 === $c
            || 0x2984 === $c
            || 0x2985 === $c
            || 0x2986 === $c
            || 0x2987 === $c
            || 0x2988 === $c
            || 0x2989 === $c
            || 0x298A === $c
            || 0x298B === $c
            || 0x298C === $c
            || 0x298D === $c
            || 0x298E === $c
            || 0x298F === $c
            || 0x2990 === $c
            || 0x2991 === $c
            || 0x2992 === $c
            || 0x2993 === $c
            || 0x2994 === $c
            || 0x2995 === $c
            || 0x2996 === $c
            || 0x2997 === $c
            || 0x2998 === $c
            || ($c >= 0x2999 && $c <= 0x29D7)
            || 0x29D8 === $c
            || 0x29D9 === $c
            || 0x29DA === $c
            || 0x29DB === $c
            || ($c >= 0x29DC && $c <= 0x29FB)
            || 0x29FC === $c
            || 0x29FD === $c
            || ($c >= 0x29FE && $c <= 0x2AFF)
            || ($c >= 0x2B00 && $c <= 0x2B2F)
            || ($c >= 0x2B30 && $c <= 0x2B44)
            || ($c >= 0x2B45 && $c <= 0x2B46)
            || ($c >= 0x2B47 && $c <= 0x2B4C)
            || ($c >= 0x2B4D && $c <= 0x2B73)
            || ($c >= 0x2B74 && $c <= 0x2B75)
            || ($c >= 0x2B76 && $c <= 0x2B95)
            || 0x2B96 === $c
            || ($c >= 0x2B97 && $c <= 0x2BFF)
            || ($c >= 0x2E00 && $c <= 0x2E01)
            || 0x2E02 === $c
            || 0x2E03 === $c
            || 0x2E04 === $c
            || 0x2E05 === $c
            || ($c >= 0x2E06 && $c <= 0x2E08)
            || 0x2E09 === $c
            || 0x2E0A === $c
            || 0x2E0B === $c
            || 0x2E0C === $c
            || 0x2E0D === $c
            || ($c >= 0x2E0E && $c <= 0x2E16)
            || 0x2E17 === $c
            || ($c >= 0x2E18 && $c <= 0x2E19)
            || 0x2E1A === $c
            || 0x2E1B === $c
            || 0x2E1C === $c
            || 0x2E1D === $c
            || ($c >= 0x2E1E && $c <= 0x2E1F)
            || 0x2E20 === $c
            || 0x2E21 === $c
            || 0x2E22 === $c
            || 0x2E23 === $c
            || 0x2E24 === $c
            || 0x2E25 === $c
            || 0x2E26 === $c
            || 0x2E27 === $c
            || 0x2E28 === $c
            || 0x2E29 === $c
            || ($c >= 0x2E2A && $c <= 0x2E2E)
            || 0x2E2F === $c
            || ($c >= 0x2E30 && $c <= 0x2E39)
            || ($c >= 0x2E3A && $c <= 0x2E3B)
            || ($c >= 0x2E3C && $c <= 0x2E3F)
            || 0x2E40 === $c
            || 0x2E41 === $c
            || 0x2E42 === $c
            || ($c >= 0x2E43 && $c <= 0x2E4F)
            || ($c >= 0x2E50 && $c <= 0x2E51)
            || 0x2E52 === $c
            || ($c >= 0x2E53 && $c <= 0x2E7F)
            || ($c >= 0x3001 && $c <= 0x3003)
            || 0x3008 === $c
            || 0x3009 === $c
            || 0x300A === $c
            || 0x300B === $c
            || 0x300C === $c
            || 0x300D === $c
            || 0x300E === $c
            || 0x300F === $c
            || 0x3010 === $c
            || 0x3011 === $c
            || ($c >= 0x3012 && $c <= 0x3013)
            || 0x3014 === $c
            || 0x3015 === $c
            || 0x3016 === $c
            || 0x3017 === $c
            || 0x3018 === $c
            || 0x3019 === $c
            || 0x301A === $c
            || 0x301B === $c
            || 0x301C === $c
            || 0x301D === $c
            || ($c >= 0x301E && $c <= 0x301F)
            || 0x3020 === $c
            || 0x3030 === $c
            || 0xFD3E === $c
            || 0xFD3F === $c
            || ($c >= 0xFE45 && $c <= 0xFE46)
        ;
    }

    public static function fromCodePoint(int ...$codePoints): string
    {
        $elements = '';
        $length = \count($codePoints);
        $i = 0;
        while ($length > $i) {
            $code = $codePoints[$i++];
            if ($code > 0x10FFFF) {
                throw RangeError($code + ' is not a valid code point');
            }

            $elements .= mb_chr($code, 'UTF-8');
        }

        return $elements;
    }

    public static function matchIdentifierAtIndex(string $s, int $index): string
    {
        $match = [];

        while (true) {
            $c = s($s)->codePointsAt($index)[0] ?? null;
            if (null === $c || self::isWhiteSpace($c) || self::isPatternSyntax($c)) {
                break;
            }

            $match[] = $c;
            $index += $c >= 0x10000 ? 2 : 1;
        }

        return self::fromCodePoint(...$match);
    }

    public static function isSafeInteger(mixed $value): bool
    {
        return \is_int($value) && is_finite($value) && abs($value) <= \PHP_INT_MAX;
    }
}
