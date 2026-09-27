<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Golden;

use PHPUnit\Framework\TestCase;

final class PandaCaseTest extends TestCase
{
    public function testTokenCssIgnoresTheWhitespaceAndLastSemicolonBeforeAClosingBrace(): void
    {
        $expectedCss = "@layer tokens {\n  .dark {\n    --a: red\n                }\n        }\n}";
        $case = self::createCase('token-css', $expectedCss);

        $this->assertTrue($case->matches("@layer tokens {\n  .dark {\n    --a: red;\n}\n}\n}"));
        $this->assertFalse($case->matches("@layer tokens {\n  .dark {\n    --a: blue;\n}\n}\n}"));
        $this->assertFalse($case->matches("@layer tokens {\n  .light {\n    --a: red;\n}\n}\n}"));
    }

    public function testOtherCasesMustMatchExactly(): void
    {
        $expectedCss = "@layer utilities {\n  .c_red {\n    color: red;\n}\n}";
        $case = self::createCase('rule-processor', $expectedCss);

        $this->assertTrue($case->matches("@layer utilities {\n  .c_red {\n    color: red;\n}\n}"));
        $this->assertFalse($case->matches("@layer utilities {\n  .c_red {\n    color: red\n}\n}"));
    }

    private static function createCase(string $kind, string $css): PandaCase
    {
        return PandaCase::fromArray([
            'id' => 'id',
            'kind' => $kind,
            'config' => 'fixture',
            'expected' => ['css' => $css],
            'source' => ['file' => 'file.test.ts'],
        ]);
    }
}
