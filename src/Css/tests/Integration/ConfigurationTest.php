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

    public static function providePreflightConfigs(): iterable
    {
        yield 'not set' => [null, false];
        yield 'false' => [false, false];
        yield 'true' => [true, true];
        yield 'a scope' => [['scope' => '.reset'], ['scope' => '.reset', 'level' => 'parent']];
        yield 'a scope and a level' => [['scope' => '.reset', 'level' => 'element'], ['scope' => '.reset', 'level' => 'element']];
        yield 'a scope, disabled' => [['enabled' => false, 'scope' => '.reset'], false];
    }

    #[DataProvider('providePreflightConfigs')]
    public function testThePreflightOptionDrivesTheBuildEngine(mixed $preflight, bool|array $expected): void
    {
        self::bootKernel(['ux_css' => null === $preflight ? [] : ['preflight' => $preflight]]);

        $engine = self::getContainer()->get('ux_css.engine');

        $this->assertSame($expected, $engine->config()['preflight']);
        $this->assertSame(false !== $expected, $engine->hasPreflight());
    }

    public function testThePreflightIsWrittenByTheCssGenerator(): void
    {
        self::bootKernel(['ux_css' => ['preflight' => ['scope' => '.reset', 'level' => 'element']]]);
        $generator = self::getContainer()->get('ux_css.css_generator');

        $css = $generator->generate([]);

        $this->assertStringStartsWith("@layer reset, tokens, utilities;\n\n@layer reset {\n  .reset {\n", $css);
        $this->assertStringContainsString("\n  img.reset {\n", $css);
    }

    public static function provideTokenCategories(): iterable
    {
        yield 'blurs' => [
            ['blurs' => ['sm' => '4px']],
            ['backdropFilter' => 'auto', 'backdropBlur' => 'sm'],
            ['--blurs-sm: 4px;', '--backdrop-blur: blur(var(--blurs-sm));'],
        ];
        yield 'letterSpacings' => [
            ['letterSpacings' => ['wide' => '0.1em']],
            ['letterSpacing' => 'wide'],
            ['--letter-spacings-wide: 0.1em;', 'letter-spacing: var(--letter-spacings-wide);'],
        ];
        yield 'borders' => [
            ['borders' => ['subtle' => '1px solid #e5e7eb']],
            ['border' => 'subtle'],
            ['--borders-subtle: 1px solid #e5e7eb;', 'border: var(--borders-subtle);'],
        ];
        yield 'borderWidths' => [
            ['borderWidths' => ['thin' => '1px']],
            ['borderWidth' => 'thin'],
            ['--border-widths-thin: 1px;', 'border-width: var(--border-widths-thin);'],
        ];
        yield 'gradients' => [
            ['gradients' => ['sunset' => 'radial-gradient(circle, #f97316, #db2777)']],
            ['bgRadial' => 'sunset'],
            ['--gradients-sunset: radial-gradient(circle, #f97316, #db2777);'],
        ];
        yield 'opacity' => [
            ['opacity' => ['half' => '0.5']],
            ['opacity' => 'half'],
            ['--opacity-half: 0.5;', 'opacity: var(--opacity-half);'],
        ];
        yield 'aspectRatios' => [
            ['aspectRatios' => ['wide' => '16 / 9']],
            ['aspectRatio' => 'wide'],
            ['--aspect-ratios-wide: 16 / 9;', 'aspect-ratio: var(--aspect-ratios-wide);'],
        ];
        yield 'cursor' => [
            ['cursor' => ['click' => 'pointer']],
            ['cursor' => 'click'],
            ['--cursor-click: pointer;', 'cursor: var(--cursor-click);'],
        ];
        yield 'assets' => [
            ['assets' => ['logo' => 'url("/images/logo.svg")']],
            ['bgImage' => 'logo'],
            ['--assets-logo: url("/images/logo.svg");', 'background-image: var(--assets-logo);'],
        ];
        yield 'containerNames' => [
            ['containerNames' => ['sidebar' => 'sidebar']],
            ['containerName' => 'sidebar'],
            ['--container-names-sidebar: sidebar;', 'container-name: var(--container-names-sidebar);'],
        ];
        yield 'animations' => [
            ['animations' => ['spin' => 'spin 1s linear infinite']],
            ['animation' => 'spin'],
            ['--animations-spin: spin 1s linear infinite;', 'animation: var(--animations-spin);'],
        ];
    }

    #[DataProvider('provideTokenCategories')]
    public function testATokenOfEachCategoryIsDeclaredAndUsed(array $tokens, array $styles, array $declarations): void
    {
        self::bootKernel(['ux_css' => ['tokens' => $tokens]]);
        $validator = self::getContainer()->get('ux_css.validator');
        $generator = self::getContainer()->get('ux_css.css_generator');

        $validator->validate($styles);
        $css = $generator->generate([$styles]);

        foreach ($declarations as $declaration) {
            $this->assertStringContainsString($declaration, $css);
        }
    }

    public static function provideCompositeTokens(): iterable
    {
        yield 'border' => [
            ['borders' => ['accent' => ['value' => ['width' => 1, 'style' => 'solid', 'color' => '#2563eb']]]],
            '--borders-accent: 1px solid #2563eb;',
        ];
        yield 'gradient' => [
            ['gradients' => ['brand' => ['value' => ['type' => 'linear', 'placement' => 'to right', 'stops' => ['#2563eb', '#db2777']]]]],
            '--gradients-brand: linear-gradient(to right, #2563eb, #db2777);',
        ];
        yield 'shadow' => [
            ['shadows' => ['card' => ['value' => ['offsetX' => 0, 'offsetY' => 4, 'blur' => 4, 'spread' => 0, 'color' => '#0000001a']]]],
            '--shadows-card: 0px 4px 4px 0px #0000001a;',
        ];
        yield 'url asset' => [
            ['assets' => ['logo' => ['value' => ['type' => 'url', 'value' => '/images/logo.svg']]]],
            '--assets-logo: url("/images/logo.svg");',
        ];
        yield 'svg asset' => [
            ['assets' => ['dot' => ['value' => ['type' => 'svg', 'value' => '<svg xmlns="http://www.w3.org/2000/svg"><circle r="1"/></svg>']]]],
            '--assets-dot: url("data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\'%3e%3ccircle r=\'1\'/%3e%3c/svg%3e");',
        ];
        yield 'gradient with stop positions' => [
            ['gradients' => ['fade' => ['value' => ['type' => 'linear', 'placement' => 'to bottom', 'stops' => [['color' => '#fff', 'position' => 0], ['color' => '#000', 'position' => 100]]]]]],
            '--gradients-fade: linear-gradient(to bottom, #fff 0px, #000 100px);',
        ];
        yield 'border with a width that has a unit' => [
            ['borders' => ['thin' => ['value' => ['width' => '0.5rem', 'style' => 'dashed', 'color' => 'red']]]],
            '--borders-thin: 0.5rem dashed red;',
        ];
    }

    public function testASemanticTokenOfEachCategoryCanReferenceATokenOrChangeWithConditions(): void
    {
        $config = [
            'tokens' => [
                'letterSpacings' => ['wide' => '0.1em'],
                'borders' => ['subtle' => '1px solid #e5e7eb'],
                'opacity' => ['dim' => 0.5],
            ],
            'semantic_tokens' => [
                'letterSpacings' => ['heading' => '{letterSpacings.wide}'],
                'borders' => [
                    'card' => '{borders.subtle}',
                    'accent' => [
                        'base' => ['width' => 1, 'style' => 'solid', 'color' => '#2563eb'],
                        '_dark' => ['width' => 2, 'style' => 'solid', 'color' => '#93c5fd'],
                    ],
                ],
                'opacity' => ['disabled' => ['base' => '{opacity.dim}', '_dark' => '0.4']],
            ],
        ];
        self::bootKernel(['ux_css' => $config]);
        $generator = self::getContainer()->get('ux_css.css_generator');

        $css = $generator->generate([['letterSpacing' => 'heading', 'border' => 'accent', 'opacity' => 'disabled']]);

        $this->assertStringContainsString('--letter-spacings-heading: var(--letter-spacings-wide);', $css);
        $this->assertStringContainsString('--borders-card: var(--borders-subtle);', $css);
        $this->assertStringContainsString('--borders-accent: 1px solid #2563eb;', $css);
        $this->assertStringContainsString('--borders-accent: 2px solid #93c5fd;', $css);
        $this->assertStringContainsString('--opacity-disabled: var(--opacity-dim);', $css);
        $this->assertStringContainsString('--opacity-disabled: 0.4;', $css);
    }

    public function testPandaDefaultTokensResolveTheTokensOfTheNewCategories(): void
    {
        self::bootKernel(['ux_css' => ['default_tokens' => 'panda']]);
        $validator = self::getContainer()->get('ux_css.validator');
        $generator = self::getContainer()->get('ux_css.css_generator');
        $styles = ['letterSpacing' => 'tight', 'backdropFilter' => 'auto', 'backdropBlur' => 'md', 'animation' => 'spin'];

        $validator->validate($styles);
        $css = $generator->generate([$styles]);

        $this->assertStringContainsString('letter-spacing: var(--letter-spacings-tight);', $css);
        $this->assertStringContainsString('--backdrop-blur: blur(var(--blurs-md));', $css);
        $this->assertStringContainsString('--animations-spin: spin 1s linear infinite;', $css);
        $this->assertStringContainsString('animation: var(--animations-spin);', $css);
    }

    #[DataProvider('provideCompositeTokens')]
    public function testCompositeTokensAcceptAnObjectInTheValueForm(array $tokens, string $declaration): void
    {
        self::bootKernel(['ux_css' => ['tokens' => $tokens]]);
        $generator = self::getContainer()->get('ux_css.css_generator');

        $css = $generator->generate([]);

        $this->assertStringContainsString($declaration, $css);
    }

    public function testPandaDefaultTokensIncludeBlursLetterSpacingsAspectRatiosAndBorders(): void
    {
        self::bootKernel(['ux_css' => ['default_tokens' => 'panda']]);
        $validator = self::getContainer()->get('ux_css.validator');
        $generator = self::getContainer()->get('ux_css.css_generator');
        $styles = ['backdropFilter' => 'auto', 'backdropBlur' => 'sm', 'letterSpacing' => 'wide', 'aspectRatio' => 'wide', 'border' => 'none'];

        $validator->validate($styles);
        $css = $generator->generate([$styles]);

        $this->assertStringContainsString('--blurs-sm: 8px;', $css);
        $this->assertStringContainsString('--letter-spacings-wide: 0.025em;', $css);
        $this->assertStringContainsString('--aspect-ratios-wide: 16 / 9;', $css);
        $this->assertStringContainsString('--borders-none: none;', $css);
    }

    public static function provideInvalidConfigs(): iterable
    {
        yield 'preflight level without a scope' => [
            ['preflight' => ['level' => 'element']],
            'The "scope" option is required when the "level" option is set.',
        ];
        yield 'preflight with an empty scope' => [
            ['preflight' => ['scope' => '']],
            'The path "ux_css.preflight.scope" cannot contain an empty value, but got "".',
        ];
        yield 'unknown preflight level' => [
            ['preflight' => ['scope' => '.reset', 'level' => 'child']],
            'The value "child" is not allowed for path "ux_css.preflight.level". Permissible values: "parent", "element"',
        ];
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
            'Circular token reference: "colors.a -> colors.b -> colors.a".',
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
        yield 'border that is not a width, a style and a color' => [
            ['tokens' => ['borders' => ['subtle' => ['value' => ['width' => 1, 'style' => 'solid']]]]],
            'The value of the "borders.subtle" token must be a string or an object like { width: 1, style: "solid", color: "#ccc" }, {"width":1,"style":"solid"} given.',
        ];
        yield 'asset of an unknown type' => [
            ['tokens' => ['assets' => ['logo' => ['value' => ['type' => 'png', 'value' => '/logo.png']]]]],
            'The value of the "assets.logo" token must be a string or an object like { type: "url", value: "/logo.svg" }, {"type":"png","value":"/logo.png"} given.',
        ];
        yield 'gradient without stops' => [
            ['semantic_tokens' => ['gradients' => ['brand' => ['value' => ['type' => 'linear', 'placement' => 'to right']]]]],
            'The value of the "gradients.brand" token must be a string or an object like { type: "linear", placement: "to right", stops: ["red", "blue"] }, {"type":"linear","placement":"to right"} given.',
        ];
        yield 'list in a category that takes none' => [
            ['tokens' => ['letterSpacings' => ['wide' => ['value' => ['0.1em']]]]],
            'The value of the "letterSpacings.wide" token must be a string or a number, array given.',
        ];
        yield 'new category with a typo' => [
            ['tokens' => ['letterSpacing' => ['wide' => '0.1em']]],
            'Unknown token category "letterSpacing". Did you mean "letterSpacings"?',
        ];
        yield 'unknown token in a semantic token of a new category' => [
            [
                'tokens' => ['blurs' => ['md' => '12px']],
                'semantic_tokens' => ['blurs' => ['glass' => '{blurs.mdd}']],
            ],
            'The "blurs.glass" token references the unknown token "blurs.mdd". Did you mean "blurs.md"?',
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
