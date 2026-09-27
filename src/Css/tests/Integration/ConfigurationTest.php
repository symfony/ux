<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\UX\Css\Engine\ClassNameGenerator;
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Tests\Fixtures\TestKernel;

final class ConfigurationTest extends KernelTestCase
{
    private const SPEC_CONFIG = [
        'tokens' => [
            'colors' => [
                'blue' => [500 => '#3b82f6', 600 => '#2563eb'],
                'gray' => [50 => '#f9fafb', 900 => '#111827'],
            ],
            'spacing' => ['sm' => '0.5rem', 'md' => '1rem', 'lg' => '2rem'],
            'radii' => ['md' => '0.375rem'],
        ],
        'semantic_tokens' => [
            'colors' => [
                'primary' => '{colors.blue.500}',
                'fg' => ['base' => '{colors.gray.900}', '_dark' => '{colors.gray.50}'],
            ],
        ],
        'conditions' => [
            'dark' => '[data-theme=dark] &',
            'expanded' => '&[aria-expanded=true]',
            'print' => '@media print',
        ],
        'breakpoints' => ['sm' => '40rem', 'md' => '48rem', 'lg' => '64rem', 'xl' => '80rem', '2xl' => '96rem'],
    ];

    public function testTheSpecConfigDrivesTheRuntimeClassNames(): void
    {
        self::bootKernel(['ux_css' => self::SPEC_CONFIG]);
        $styles = [
            'p' => 'md',
            '_hover' => ['color' => 'primary'],
            '_expanded' => ['color' => 'fg'],
            'md' => ['p' => 'lg'],
        ];

        $generator = self::getContainer()->get('ux_css.class_name_generator');

        $this->assertInstanceOf(ClassNameGenerator::class, $generator);
        $this->assertSame('p_md hover:c_primary expanded:c_fg md:p_lg', $generator->generate($styles));
    }

    public function testTheSpecConfigDrivesTheBuildEngine(): void
    {
        self::bootKernel(['ux_css' => self::SPEC_CONFIG]);

        $engine = self::getContainer()->get('ux_css.engine');

        $this->assertInstanceOf(Engine::class, $engine);
        $this->assertSame(['padding' => 'var(--spacing-md)'], $engine->utilities()->transform('p', 'md')['styles']);
        $this->assertSame('var(--colors-blue-500)', $engine->tokens()->getValue('colors.primary'));
        $this->assertSame('[data-theme=dark] &', $engine->config()['conditions']['dark']);
        $this->assertSame('48rem', $engine->config()['theme']['breakpoints']['md']);
    }

    public function testTheCssGeneratorUsesTheProjectTokens(): void
    {
        self::bootKernel(['ux_css' => self::SPEC_CONFIG]);
        $generator = self::getContainer()->get('ux_css.css_generator');

        $css = $generator->generate([['color' => 'primary']]);

        $this->assertStringContainsString('--colors-primary: var(--colors-blue-500);', $css);
    }

    public function testEmptyConfigUsesPandaDefaults(): void
    {
        self::bootKernel();
        $generator = self::getContainer()->get('ux_css.class_name_generator');

        $classNames = $generator->generate(['display' => 'flex', 'md' => ['p' => 4]]);

        $this->assertSame('d_flex md:p_4', $classNames);
    }

    public function testNoDefaultTokensUnlessEnabled(): void
    {
        self::bootKernel();
        $generator = self::getContainer()->get('ux_css.css_generator');

        $css = $generator->generate([]);

        $this->assertStringNotContainsString('--colors-red-500', $css);
    }

    public function testPandaDefaultTokensCanBeEnabled(): void
    {
        self::bootKernel(['ux_css' => ['default_tokens' => 'panda']]);
        $validator = self::getContainer()->get('ux_css.validator');
        $generator = self::getContainer()->get('ux_css.css_generator');

        $validator->validate(['bg' => 'red.500', 'p' => '4', 'rounded' => 'md']);
        $css = $generator->generate([['bg' => 'red.500']]);

        $this->assertStringContainsString('--colors-red-500: #ef4444;', $css);
        $this->assertStringContainsString('--spacing-4: 1rem;', $css);
    }

    public function testAProjectTokenReplacesTheDefaultTokenWithTheSamePath(): void
    {
        $config = ['default_tokens' => 'panda', 'tokens' => ['colors' => ['red' => ['500' => '#f00']]]];
        self::bootKernel(['ux_css' => $config]);
        $generator = self::getContainer()->get('ux_css.css_generator');

        $css = $generator->generate([]);

        $this->assertStringContainsString('--colors-red-500: #f00;', $css);
        $this->assertStringContainsString('--colors-red-600: #dc2626;', $css);
    }

    public static function provideListTokens(): iterable
    {
        yield 'list' => [['fonts' => ['sans' => ['Inter', 'sans-serif']]], '--fonts-sans: Inter, sans-serif;'];
        yield 'list in the value form' => [['fonts' => ['sans' => ['value' => ['Inter', 'sans-serif']]]], '--fonts-sans: Inter, sans-serif;'];
        yield 'shadow list' => [['shadows' => ['sm' => ['0 0 1px red']]], '--shadows-sm: 0 0 1px red;'];
    }

    #[DataProvider('provideListTokens')]
    public function testAListTokenOfTheProjectReplacesTheDefaultOne(array $tokens, string $variable): void
    {
        self::bootKernel(['ux_css' => ['default_tokens' => 'panda', 'tokens' => $tokens]]);
        $generator = self::getContainer()->get('ux_css.css_generator');

        $css = $generator->generate([]);

        $this->assertStringContainsString($variable, $css);
    }

    public function testTokensAcceptPandasValueForm(): void
    {
        $config = [
            'tokens' => ['spacing' => ['sm' => ['value' => '0.5rem', 'description' => 'Small gaps']]],
            'semantic_tokens' => ['spacing' => ['gutter' => ['value' => ['base' => '{spacing.sm}', 'md' => '1rem']]]],
        ];
        self::bootKernel(['ux_css' => $config]);
        $generator = self::getContainer()->get('ux_css.css_generator');

        $css = $generator->generate([['p' => 'sm']]);

        $this->assertStringContainsString('--spacing-sm: 0.5rem;', $css);
        $this->assertStringContainsString('--spacing-gutter: var(--spacing-sm);', $css);
    }

    public static function provideInvalidConfigs(): iterable
    {
        yield 'unknown token category' => [
            ['tokens' => ['colours' => ['red' => '#f00']]],
            'Unknown token category "colours". Did you mean "colors"?',
        ];
        yield 'unknown semantic token category' => [
            ['semantic_tokens' => ['spacings' => ['gutter' => '1rem']]],
            'Unknown token category "spacings". Did you mean "spacing"?',
        ];
        yield 'broken reference' => [
            [
                'tokens' => ['colors' => ['blue' => [500 => '#00f']]],
                'semantic_tokens' => ['colors' => ['primary' => '{colors.blue.50}']],
            ],
            'The "colors.primary" token references the unknown token "colors.blue.50". Did you mean "colors.blue.500"?',
        ];
        yield 'circular reference' => [
            ['semantic_tokens' => ['colors' => ['a' => '{colors.b}', 'b' => '{colors.a}']]],
            'Circular token reference: colors.a -> colors.b -> colors.a.',
        ];
        yield 'unknown condition in a semantic token' => [
            [
                'tokens' => ['colors' => ['white' => '#fff', 'black' => '#000']],
                'semantic_tokens' => ['colors' => ['fg' => ['base' => '{colors.black}', '_drak' => '{colors.white}']]],
            ],
            'The "colors.fg" token uses the unknown condition "_drak". Did you mean "_dark"?',
        ];
        yield 'condition without & or @' => [
            ['conditions' => ['active' => '.is-active']],
            'The "active" condition must contain "&" or start with "@", ".is-active" given.',
        ];
        yield 'invalid static css' => [
            ['static_css' => ['css' => [['properties' => ['display' => ['flexx']]]]]],
            'The ux_css.static_css rules are invalid: Invalid value "flexx" for "display". Did you mean "flex"?',
        ];
        yield 'token value that is a hash' => [
            ['tokens' => ['spacing' => ['sm' => ['value' => ['min' => '0.5rem']]]]],
            'The value of the "spacing.sm" token must be a string or a number, array given.',
        ];
        yield 'breakpoint named like a shorthand' => [
            ['breakpoints' => ['p' => '40rem']],
            'The "p" breakpoint has the same name as a CSS property or shorthand.',
        ];
    }

    #[DataProvider('provideInvalidConfigs')]
    public function testInvalidConfigsAreRejectedWhenTheContainerCompiles(array $config, string $message): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($message);

        self::bootKernel(['ux_css' => $config]);
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return new TestKernel($options['ux_css'] ?? []);
    }
}
