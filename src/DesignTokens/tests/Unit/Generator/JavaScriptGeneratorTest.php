<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Generator\JavaScriptGenerator;
use Symfony\UX\DesignTokens\Token\ColorToken;
use Symfony\UX\DesignTokens\Token\DimensionToken;
use Symfony\UX\DesignTokens\Token\NumberToken;
use Symfony\UX\DesignTokens\TokenTree;

#[CoversClass(JavaScriptGenerator::class)]
#[CoversClass(TokenTree::class)]
final class JavaScriptGeneratorTest extends TestCase
{
    public function testGeneratesACssReadyEsmModule(): void
    {
        $output = new JavaScriptGenerator()->generate([
            'color' => ['brand' => new ColorToken(['colorSpace' => 'srgb', 'components' => [0.2, 0.4, 0.8]])],
            'space' => ['md' => new DimensionToken(['value' => 1, 'unit' => 'rem'])],
        ]);

        self::assertSame(['color.brand' => 'color(srgb 0.2 0.4 0.8)', 'space.md' => '1rem'], self::tokens($output));
        self::assertStringContainsString('export function token(path)', $output);
        self::assertStringContainsString('Unknown design token: "${path}".', $output);
        self::assertStringEndsWith("\n", $output);
    }

    public function testGeneratesAnEmptyObject(): void
    {
        self::assertSame([], self::tokens(new JavaScriptGenerator()->generate([])));
    }

    public function testEveryPathBecomesAnOwnProperty(): void
    {
        $output = new JavaScriptGenerator()->generate(['__proto__' => new NumberToken(1)]);

        self::assertStringContainsString('export const tokens = Object.freeze(JSON.parse(', $output);
        self::assertSame(['__proto__' => '1'], self::tokens($output));
    }

    /** @return array<string, string> */
    private static function tokens(string $module): array
    {
        self::assertSame(1, preg_match('/Object\.freeze\(JSON\.parse\((".*")\)\);/', $module, $matches));
        $json = json_decode($matches[1], false, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_string($json));
        $tokens = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($tokens));

        return $tokens;
    }
}
