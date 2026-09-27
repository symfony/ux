<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\CssGenerator;
use Symfony\UX\Css\Engine\Engine;

final class CssGeneratorTest extends TestCase
{
    private const PROJECT = [
        'theme' => [
            'tokens' => ['colors' => ['red' => ['value' => '#f00']], 'spacing' => ['md' => ['value' => '1rem']]],
            'semanticTokens' => ['colors' => ['primary' => ['value' => '{colors.red}']]],
            'breakpoints' => ['md' => '48rem'],
        ],
    ];

    private const STYLES = [
        ['p' => 'md', 'color' => 'primary'],
        ['color' => 'primary', 'md' => ['p' => 'md']],
    ];

    public function testReadableOutput(): void
    {
        $css = self::generator()->generate(self::STYLES);

        $this->assertSame(<<<'CSS'
            @layer tokens, utilities;

            @layer tokens {
              :where(:root, :host) {
                --colors-red: #f00;
                --spacing-md: 1rem;
                --breakpoints-md: 48rem;
                --sizes-breakpoint-md: 48rem;
                --colors-primary: var(--colors-red);
            }
            }

            @layer utilities {
              .p_md {
                padding: var(--spacing-md);
            }

              .c_primary {
                color: var(--colors-primary);
            }

              @media screen and (min-width: 48rem) {
                .md\:p_md {
                  padding: var(--spacing-md);
            }
            }
            }

            CSS, $css);
    }

    public function testCompactOutput(): void
    {
        $css = self::generator()->generate(self::STYLES, compact: true);

        $this->assertSame(
            '@layer tokens,utilities;@layer tokens{:where(:root, :host){--colors-red:#f00;--spacing-md:1rem;--breakpoints-md:48rem;--sizes-breakpoint-md:48rem;--colors-primary:var(--colors-red)}}@layer utilities{.p_md{padding:var(--spacing-md)}.c_primary{color:var(--colors-primary)}@media screen and (min-width: 48rem){.md\:p_md{padding:var(--spacing-md)}}}',
            $css,
        );
    }

    public function testCompactOutputKeepsImportantAndSelectorLists(): void
    {
        $generator = new CssGenerator(Engine::fromProjectConfig([]));
        $styles = [
            ['color' => 'red !important'],
            ['_hover' => ['color' => 'blue'], '_focus' => ['color' => 'blue']],
        ];

        $css = $generator->generate($styles, compact: true);

        $this->assertStringContainsString('.c_red\!{color:red!important}', $css);
        $this->assertStringContainsString('.focus\:c_blue:is(:focus, [data-focus]),.hover\:c_blue:is(:hover, [data-hover]){color:blue}', $css);
    }

    public function testARuleUsedInSeveralPlacesIsWrittenOnce(): void
    {
        $css = self::generator()->generate([...self::STYLES, ...self::STYLES]);

        $this->assertSame(1, substr_count($css, '.c_primary {'));
    }

    public function testTokensAreWrittenEvenWhenNoStyleUsesThem(): void
    {
        $css = self::generator()->generate([]);

        $this->assertStringStartsWith("@layer tokens, utilities;\n\n@layer tokens {\n", $css);
        $this->assertStringNotContainsString('@layer utilities {', $css);
    }

    public function testBreakpointsAreTokensToo(): void
    {
        $generator = new CssGenerator(Engine::fromProjectConfig([]));

        $css = $generator->generate([]);

        $this->assertStringContainsString("--breakpoints-md: 768px;\n", $css);
    }

    public function testNothingIsWrittenWithoutTokensNorStyles(): void
    {
        $generator = new CssGenerator(new Engine([]));

        $css = $generator->generate([]);

        $this->assertSame('', $css);
    }

    private static function generator(): CssGenerator
    {
        return new CssGenerator(Engine::fromProjectConfig(self::PROJECT));
    }
}
