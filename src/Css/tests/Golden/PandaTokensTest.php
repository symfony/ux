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
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Engine\Tokens;

final class PandaTokensTest extends TestCase
{
    private const FIXTURES = __DIR__.'/../Fixtures/Panda';

    private static ?Tokens $tokens = null;

    /**
     * @var array{vars: array<string, string>, values: array<string, mixed>, categories: array<string, array<string, string>>, colorPalettes: array<string, array<string, string>>, cssVars: array<string, array<string, mixed>>}|null
     */
    private static ?array $expected = null;

    public function testVariablesMatchPanda(): void
    {
        $tokens = self::tokens();

        foreach (self::expected()['vars'] as $name => $var) {
            $this->assertSame($var, $tokens->getVar((string) $name), $name);
        }
    }

    public function testValuesMatchPanda(): void
    {
        $tokens = self::tokens();

        foreach (self::expected()['values'] as $name => $value) {
            $this->assertSame($value, $tokens->getValue((string) $name), $name);
        }
    }

    public function testCategoriesMatchPanda(): void
    {
        $tokens = self::tokens();

        foreach (self::expected()['categories'] as $category => $values) {
            $this->assertEquals($values, $tokens->getCategoryValues((string) $category), $category);
        }
    }

    public function testColorPalettesMatchPanda(): void
    {
        $tokens = self::tokens();
        $paletteNames = array_map(strval(...), array_keys(self::expected()['colorPalettes']));

        $this->assertSame($paletteNames, $tokens->getColorPaletteNames());
        foreach (self::expected()['colorPalettes'] as $palette => $values) {
            $this->assertSame($values, $tokens->getColorPalette((string) $palette), $palette);
        }
    }

    public function testCssVariablesMatchPanda(): void
    {
        $tokens = self::tokens();

        $this->assertSame(self::expected()['cssVars'], $tokens->getVars());
    }

    private static function tokens(): Tokens
    {
        if (null !== self::$tokens) {
            return self::$tokens;
        }

        $json = file_get_contents(self::FIXTURES.'/configs/fixture.json');
        $config = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);

        return self::$tokens = new Engine($config)->tokens();
    }

    private static function expected(): array
    {
        return self::$expected ??= json_decode(
            file_get_contents(self::FIXTURES.'/tokens.json'),
            true,
            flags: \JSON_THROW_ON_ERROR,
        );
    }
}
