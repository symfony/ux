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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\UX\Css\Tests\Fixtures\Dtcg;
use Symfony\UX\Css\Tests\Fixtures\TestKernel;

final class ConfigurationTest extends KernelTestCase
{
    public function testTheDesignTokensDriveTheRuntimeClassNames(): void
    {
        self::bootKernel();
        $generator = self::getContainer()->get('ux_css.class_name_generator');
        $styles = [
            'p' => 'md',
            '_hover' => ['color' => 'primary'],
            '_dark' => ['color' => 'fg'],
            'md' => ['p' => 'lg'],
        ];

        $classNames = $generator->generate($styles);

        $this->assertSame('p_md hover:c_primary dark:c_fg md:p_lg', $classNames);
    }

    public function testRulesReadTheVariablesOfDesignTokens(): void
    {
        self::bootKernel();
        $engine = self::getContainer()->get('ux_css.engine');

        $styles = $engine->utilities()->transform('p', 'md')['styles'];

        $this->assertSame(['padding' => 'var(--dt-dimension-spacing-md)'], $styles);
    }

    public function testTheStylesheetDeclaresNoTokenVariable(): void
    {
        self::bootKernel();
        $generator = self::getContainer()->get('ux_css.css_generator');

        $css = $generator->generate([['color' => 'primary', 'p' => 'md']]);

        $this->assertStringContainsString('color: var(--dt-color-primary)', $css);
        $this->assertStringNotContainsString('--dt-color-primary:', $css);
        $this->assertStringNotContainsString('--colors-', $css);
    }

    public function testTheCssPrefixOfDesignTokensNamesTheVariables(): void
    {
        self::bootKernel(['design_tokens' => ['css_prefix' => 'brand']]);
        $engine = self::getContainer()->get('ux_css.engine');

        $styles = $engine->utilities()->transform('color', 'primary')['styles'];

        $this->assertSame(['color' => 'var(--brand-color-primary)'], $styles);
    }

    public function testDarkStylesFollowTheDarkVariablesOfDesignTokens(): void
    {
        self::bootKernel();
        $generator = self::getContainer()->get('ux_css.css_generator');

        $css = $generator->generate([['_dark' => ['color' => 'fg']]]);

        $this->assertStringContainsString(':root[data-theme="dark"] .dark\:c_fg', $css);
        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $css);
        $this->assertStringContainsString(':root:not([data-theme="light"]) .dark\:c_fg', $css);
    }

    public function testBreakpointsComeFromTheDesignTokens(): void
    {
        $tokens = [...self::tokens(), 'breakpoint' => ['tablet' => Dtcg::dimension(30)]];
        self::bootKernel(['tokens' => $tokens]);
        $generator = self::getContainer()->get('ux_css.css_generator');

        $css = $generator->generate([['tablet' => ['p' => 'lg']]]);

        $this->assertStringContainsString('@media screen and (min-width: 30rem)', $css);
    }

    public function testPandaBreakpointsApplyWithoutBreakpointTokens(): void
    {
        self::bootKernel();
        $generator = self::getContainer()->get('ux_css.css_generator');

        $css = $generator->generate([['md' => ['p' => 'lg']]]);

        $this->assertStringContainsString('@media screen and (min-width: 48rem)', $css);
    }

    public function testEveryResolverPermutationIsRead(): void
    {
        $dir = TestKernel::temporaryDirectory();
        self::writeResolverFixture($dir);
        $designTokens = ['resolver' => ['path' => $dir.'/theme.resolver.json']];
        self::bootKernel(['tokens' => [], 'design_tokens' => $designTokens]);
        $validator = self::getContainer()->get('ux_css.validator');

        $validator->validate(['color' => 'accent', 'bg' => 'fg']);

        $this->addToAssertionCount(1);
    }

    public function testAChangeToAReferencedDocumentRebuildsTheContainer(): void
    {
        $dir = TestKernel::temporaryDirectory();
        self::writeResolverFixture($dir);
        $options = [
            'tokens' => [],
            'design_tokens' => ['resolver' => ['path' => $dir.'/theme.resolver.json']],
            'build_dir' => $dir.'/build',
        ];
        self::bootKernel($options);
        self::ensureKernelShutdown();
        $brandFile = $dir.'/brand-b.tokens.json';
        $brand = json_decode(file_get_contents($brandFile), true);
        $brand['color']['highlight'] = Dtcg::color('#ff0');
        file_put_contents($brandFile, json_encode($brand));
        touch($brandFile, time() + 10);

        self::bootKernel($options);
        $validator = self::getContainer()->get('ux_css.validator');

        $validator->validate(['color' => 'highlight']);
        $this->addToAssertionCount(1);
    }

    public function testEmptyConfigUsesPandaDefaults(): void
    {
        self::bootKernel(['tokens' => []]);
        $generator = self::getContainer()->get('ux_css.class_name_generator');

        $classNames = $generator->generate(['display' => 'flex', 'md' => ['p' => 4]]);

        $this->assertSame('d_flex md:p_4', $classNames);
    }

    public static function provideInvalidConfigs(): iterable
    {
        yield 'condition without & or @' => [
            ['ux_css' => ['conditions' => ['active' => '.is-active']]],
            'The "active" condition must contain "&" or start with "@", ".is-active" given.',
        ];
        yield 'invalid static css' => [
            ['ux_css' => ['static_css' => ['css' => [['properties' => ['display' => ['flexx']]]]]]],
            'The ux_css.static_css rules are invalid: Invalid value "flexx" for "display". Did you mean "flex"?',
        ];
        yield 'breakpoint named like a shorthand' => [
            ['tokens' => [...self::tokens(), 'breakpoint' => ['p' => Dtcg::dimension(40)]]],
            'The "p" breakpoint has the same name as a CSS property or shorthand.',
        ];
        yield 'design token path from an environment variable' => [
            ['tokens' => [], 'design_tokens' => ['paths' => ['%env(TOKENS_FILE)%']]],
            'UX CSS reads the design tokens when the container compiles, so the "%env(TOKENS_FILE)%" design token path cannot come from an environment variable.',
        ];
    }

    #[DataProvider('provideInvalidConfigs')]
    public function testInvalidConfigsAreRejectedWhenTheContainerCompiles(array $options, string $message): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($message);

        self::bootKernel($options);
    }

    public function testDesignTokensMustBeRegistered(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('UX CSS reads its tokens from UX Design Tokens. Register "Symfony\UX\DesignTokens\UXDesignTokensBundle" in "config/bundles.php".');

        self::bootKernel(['bundle' => false, 'tokens' => []]);
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return new TestKernel(
            $options['ux_css'] ?? [],
            buildDir: $options['build_dir'] ?? null,
            designTokens: $options['tokens'] ?? self::tokens(),
            designTokensConfig: $options['design_tokens'] ?? [],
            designTokensBundle: $options['bundle'] ?? true,
        );
    }

    private static function tokens(): array
    {
        return [
            'color' => [
                'blue' => ['500' => Dtcg::color('#3b82f6')],
                'gray' => ['50' => Dtcg::color('#f9fafb'), '900' => Dtcg::color('#111827')],
                'primary' => Dtcg::alias('color.blue.500'),
                'fg' => Dtcg::alias('color.gray.900'),
            ],
            'dimension' => [
                'spacing' => [
                    'sm' => Dtcg::dimension(0.5),
                    'md' => Dtcg::dimension(1),
                    'lg' => Dtcg::dimension(2),
                ],
                'radius' => ['md' => Dtcg::dimension(0.375)],
            ],
        ];
    }

    private static function writeResolverFixture(string $dir): void
    {
        $filesystem = new Filesystem();
        $foundation = ['color' => ['fg' => Dtcg::color('#111')]];
        $brandA = ['color' => ['accent' => Dtcg::color('#00f')]];
        $brandB = ['color' => ['accent' => Dtcg::color('#f0f')]];
        $resolver = [
            'version' => '2025.10',
            'sets' => ['foundation' => ['sources' => [['$ref' => 'foundation.tokens.json']]]],
            'modifiers' => [
                'brand' => [
                    'contexts' => [
                        'a' => [['$ref' => 'brand-a.tokens.json']],
                        'b' => [['$ref' => 'brand-b.tokens.json']],
                    ],
                ],
            ],
            'resolutionOrder' => [['$ref' => '#/sets/foundation'], ['$ref' => '#/modifiers/brand']],
        ];
        $filesystem->dumpFile($dir.'/foundation.tokens.json', json_encode($foundation));
        $filesystem->dumpFile($dir.'/brand-a.tokens.json', json_encode($brandA));
        $filesystem->dumpFile($dir.'/brand-b.tokens.json', json_encode($brandB));
        $filesystem->dumpFile($dir.'/theme.resolver.json', json_encode($resolver));
    }
}
