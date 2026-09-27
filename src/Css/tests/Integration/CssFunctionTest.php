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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\UX\Css\Engine\CssEscaper;
use Symfony\UX\Css\Tests\Fixtures\TestKernel;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Source;

final class CssFunctionTest extends KernelTestCase
{
    private const CONFIG = [
        'tokens' => ['colors' => ['red' => '#f00', 'blue' => '#00f'], 'spacing' => ['md' => '1rem', 'lg' => '2rem']],
        'semantic_tokens' => ['colors' => ['primary' => '{colors.red}', 'fg' => '{colors.blue}']],
    ];

    public function testAConstantHashCompilesToItsClasses(): void
    {
        $template = '<div class="{{ css({ p: \'md\', color: \'primary\', _hover: { color: \'fg\' } }) }}"></div>';

        $html = $this->render($template);
        $compiled = $this->compile($template);

        $this->assertSame('<div class="p_md c_primary hover:c_fg"></div>', $html);
        $this->assertStringContainsString('"p_md c_primary hover:c_fg"', $compiled);
        $this->assertStringNotContainsString('CssRuntime', $compiled);
    }

    public function testATernaryWithConstantBranchesCompilesToBothClassLists(): void
    {
        $template = '{{ css({ p: \'md\', color: active ? \'primary\' : \'fg\' }) }}';

        $whenActive = $this->render($template, ['active' => true]);
        $whenInactive = $this->render($template, ['active' => false]);
        $compiled = $this->compile($template);

        $this->assertSame('p_md c_primary', $whenActive);
        $this->assertSame('p_md c_fg', $whenInactive);
        $this->assertStringNotContainsString('CssRuntime', $compiled);
    }

    public function testATernaryOfHashesCompilesToBothClassLists(): void
    {
        $template = '{{ css(active ? { color: \'primary\' } : { p: \'lg\' }) }}';

        $whenActive = $this->render($template, ['active' => true]);
        $whenInactive = $this->render($template, ['active' => false]);
        $compiled = $this->compile($template);

        $this->assertSame('c_primary', $whenActive);
        $this->assertSame('p_lg', $whenInactive);
        $this->assertStringNotContainsString('CssRuntime', $compiled);
    }

    public function testATernaryOfHashesUnderAConditionCompilesToBothClassLists(): void
    {
        $template = '{{ css({ p: \'md\', _hover: active ? { color: \'primary\' } : { color: \'fg\' } }) }}';

        $whenActive = $this->render($template, ['active' => true]);
        $whenInactive = $this->render($template, ['active' => false]);
        $compiled = $this->compile($template);

        $this->assertSame('p_md hover:c_primary', $whenActive);
        $this->assertSame('p_md hover:c_fg', $whenInactive);
        $this->assertStringNotContainsString('CssRuntime', $compiled);
    }

    public function testADynamicHashUnderAConditionIsResolvedAtRuntime(): void
    {
        $template = '{{ css({ p: \'md\', _hover: hoverStyles, md: wide }) }}';
        $context = ['hoverStyles' => ['color' => 'fg'], 'wide' => ['p' => 'lg']];

        $classes = $this->render($template, $context);

        $this->assertSame('p_md hover:c_fg md:p_lg', $classes);
    }

    public function testAHashWithASpreadIsResolvedAtRuntime(): void
    {
        $template = '{{ css({ ...base, p: \'md\' }) }}';

        $classes = $this->render($template, ['base' => ['color' => 'fg']]);

        $this->assertSame('c_fg p_md', $classes);
    }

    public function testAVariableSetOnceBeforeTheCallIsResolvedWhenTheTemplateCompiles(): void
    {
        $template = <<<'TWIG'
            {% set color = 'primary' %}
            {{ css({ color }) }}|{{ css({ color: color }) }}|{% if active %}{{ css({ color: color, p: 'md' }) }}{% endif %}
            TWIG;

        $html = $this->render($template, ['active' => true]);
        $compiled = $this->compile($template);

        $this->assertSame('c_primary|c_primary|c_primary p_md', trim($html));
        $this->assertStringNotContainsString('CssRuntime', $compiled);
    }

    public function testAHashSetOnceBeforeTheCallIsResolvedWhenTheTemplateCompiles(): void
    {
        $template = "{% set styles = { color: 'primary', p: 'md' } %}{{ css(styles) }}";

        $html = $this->render($template);
        $compiled = $this->compile($template);

        $this->assertSame('c_primary p_md', $html);
        $this->assertStringNotContainsString('CssRuntime', $compiled);
    }

    public static function provideVariablesThatCanChange(): iterable
    {
        yield 'set twice' => ["{% set color = 'fg' %}{% if active %}{% set color = 'primary' %}{% endif %}{{ css({ color }) }}"];
        yield 'set in a condition' => ["{% if active %}{% set color = 'primary' %}{% endif %}{{ css({ color }) }}"];
        yield 'set after the call' => ["{{ css({ color }) }}{% set color = 'fg' %}"];
        yield 'loop variable' => ["{% for color in ['primary'] %}{% set color = 'fg' %}{% endfor %}{{ css({ color }) }}"];
        yield 'for target after a set' => ["{% set color = 'fg' %}{% for color in ['primary'] %}{{ css({ color }) }}{% endfor %}"];
        yield 'with' => ["{% set color = 'primary' %}{% with { color: 'fg' } %}{{ css({ color }) }}{% endwith %}"];
        yield 'with only' => ["{% set color = 'primary' %}{% with { color: 'fg' } only %}{{ css({ color }) }}{% endwith %}"];
        yield 'whole hash under with' => ["{% set styles = { color: 'primary' } %}{% with { styles: { color: 'fg' } } %}{{ css(styles) }}{% endwith %}"];
        yield 'loop' => ["{% set loop = { color: 'primary' } %}{% for i in [1] %}{{ css(loop) }}{% endfor %}"];
    }

    #[DataProvider('provideVariablesThatCanChange')]
    public function testAVariableThatCanChangeIsResolvedAtRuntime(string $template): void
    {
        $compiled = $this->compile($template);

        $this->assertStringContainsString('CssRuntime', $compiled);
    }

    public function testAFailedCompilationLeavesNoConstantForTheNextTemplates(): void
    {
        $setting = "{% set color = 'primary' %}{{ css({ color }) }}";
        $using = '{{ css({ color: color }) }}';
        $compiled = [];

        try {
            $this->compile('{{ css({ colr: color }) }}');
        } catch (SyntaxError) {
        }
        for ($i = 0; $i < 50; ++$i) {
            $this->compile($setting);
            $compiled[] = $this->compile($using);
        }

        foreach ($compiled as $source) {
            $this->assertStringContainsString('CssRuntime', $source);
        }
    }

    public function testAnInvalidValueSetBeforeTheCallIsASyntaxError(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown colors token "primry"');

        $this->compile("{% set color = 'primry' %}\n{{ css({ color }) }}");
    }

    public function testTheDynamicPartOfAHashIsResolvedAtRuntime(): void
    {
        $template = '{{ css({ p: \'md\', _hover: { color: color } }) }}';

        $classes = $this->render($template, ['color' => 'fg']);
        $compiled = $this->compile($template);

        $this->assertSame('p_md hover:c_fg', $classes);
        $this->assertStringContainsString('"p_md"', $compiled);
    }

    public function testADynamicHashIsResolvedAtRuntime(): void
    {
        $styles = ['p' => 'md', 'md' => ['p' => 'lg'], '_hover' => ['color' => 'primary']];

        $html = $this->render('{{ css(styles) }}', ['styles' => $styles]);

        $this->assertSame('p_md md:p_lg hover:c_primary', $html);
    }

    public static function provideRealisticHashes(): iterable
    {
        yield 'flat' => ['{ display: \'flex\', alignItems: \'center\', gap: \'md\', p: \'lg\', color: \'primary\' }', ['display' => 'flex', 'alignItems' => 'center', 'gap' => 'md', 'p' => 'lg', 'color' => 'primary']];
        yield 'conditions' => ['{ color: \'fg\', _hover: { color: \'primary\', bg: \'fg\' }, _dark: { _focus: { color: \'primary\' } } }', ['color' => 'fg', '_hover' => ['color' => 'primary', 'bg' => 'fg'], '_dark' => ['_focus' => ['color' => 'primary']]]];
        yield 'responsive' => ['{ p: { base: \'md\', md: \'lg\' }, lg: { display: \'grid\' } }', ['p' => ['base' => 'md', 'md' => 'lg'], 'lg' => ['display' => 'grid']]];
        yield 'numbers' => ['{ zIndex: 10, opacity: 0.5, flexGrow: 1 }', ['zIndex' => 10, 'opacity' => 0.5, 'flexGrow' => 1]];
    }

    #[DataProvider('provideRealisticHashes')]
    public function testStaticAndDynamicCallsGiveTheSameClasses(string $hash, array $styles): void
    {
        $dynamicClasses = $this->render('{{ css(styles) }}', ['styles' => $styles]);
        $staticClasses = $this->render('{{ css('.$hash.') }}');

        $this->assertSame($dynamicClasses, $staticClasses);
    }

    public static function provideValuesWrittenDifferentlyByPandaRuntime(): iterable
    {
        yield 'numeric string' => [['opacity' => '0.50']];
        yield 'repeated spaces' => [['display' => 'inline  flex']];
        yield 'hexadecimal string' => [['zIndex' => '0x10']];
        yield 'top-level base' => [['base' => ['color' => 'fg'], 'color' => 'primary']];
    }

    #[DataProvider('provideValuesWrittenDifferentlyByPandaRuntime')]
    public function testStaticClassesAlwaysHaveTheirCss(array $styles): void
    {
        $css = self::getContainer()->get('ux_css.css_generator')->generate([$styles]);
        $classNames = self::getContainer()->get('ux_css.engine')->classNames($styles);

        foreach (explode(' ', $classNames) as $class) {
            $this->assertStringContainsString('.'.CssEscaper::escape($class).' {', $css);
        }
    }

    public function testDynamicValuesCannotBreakTheAttribute(): void
    {
        self::bootKernel(['debug' => false]);

        $html = $this->render('<div class="{{ css(styles) }}"></div>', ['styles' => ['content' => '"><script>']]);

        $this->assertSame('<div class="content_&quot;&gt;&lt;script&gt;"></div>', $html);
    }

    public function testStaticValuesAreEscapedToo(): void
    {
        $html = $this->render('<div class="{{ css({ w: \'[a&b>c]\' }) }}"></div>');

        $this->assertSame('<div class="w_[a&amp;b&gt;c]"></div>', $html);
    }

    public static function provideInvalidTemplates(): iterable
    {
        yield 'unknown property' => ["\n\n{{ css({ colr: 'primary' }) }}", 'Unknown property "colr". Did you mean "color"?', 3];
        yield 'unknown token in a ternary' => ["{{ css({ color: active ? 'primry' : 'fg' }) }}", 'Unknown colors token "primry". Did you mean "primary"?', 1];
        yield 'unknown condition around a dynamic value' => ["\n{{ css({ _hovr: { color: color } }) }}", 'Unknown condition "_hovr". Did you mean "_hover"?', 2];
        yield 'several hashes' => ["{{ css({ color: 'fg' }, { p: 'md' }) }}", 'css() takes a single hash of styles.', 1];
        yield 'negated string' => ["{{ css({ m: -'md' }) }}", 'Only a number can be negated. Write a negative token as a string, like "-md".', 1];
        yield 'negated sequence' => ["{{ css({ m: -['md'] }) }}", 'Only a number can be negated. Write a negative token as a string, like "-md".', 1];
        yield 'value the engine refuses' => ["\n{{ css({ zIndex: 1e999 }) }}", 'The number "INF" cannot be used as a style value.', 2];
    }

    #[DataProvider('provideInvalidTemplates')]
    public function testInvalidStylesAreSyntaxErrors(string $template, string $message, int $line): void
    {
        try {
            $this->compile($template);
            $this->fail('The template should not compile.');
        } catch (SyntaxError $e) {
            $this->assertSame($message, $e->getRawMessage());
            $this->assertSame($line, $e->getTemplateLine());
        }
    }

    public function testDynamicStylesAreValidatedInDebug(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Unknown colors token "primry". Did you mean "primary"?');

        $this->render('{{ css(styles) }}', ['styles' => ['color' => 'primry']]);
    }

    public function testLintTwigReportsInvalidStyles(): void
    {
        $application = new Application(self::bootKernel());
        $tester = new CommandTester($application->find('lint:twig'));

        $tester->execute(['filename' => [__DIR__.'/../Fixtures/invalid_templates/unknown_property.html.twig']]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Unknown property "colr". Did you mean "color"?', $tester->getDisplay());
        $this->assertStringContainsString('3', $tester->getDisplay());
    }

    public function testTheVisitorCollectsEveryStaticHashAndBothBranchesOfATernary(): void
    {
        $template = '{{ css({ p: \'md\', color: active ? \'primary\' : \'fg\', _hover: { color: color } }) }}';
        $visitor = self::getContainer()->get('ux_css.twig.node_visitor');
        $collected = [];
        $visitor->collectInto(static function (array $styles) use (&$collected): void {
            $collected[] = $styles;
        });

        $twig = self::twig();
        $twig->parse($twig->tokenize(new Source($template, 'collect.html.twig')));
        $visitor->collectInto(null);

        $this->assertSame([['p' => 'md'], ['color' => 'primary'], ['color' => 'fg']], $collected);
    }

    public function testTheVisitorCollectsBothBranchesOfATernaryOfHashes(): void
    {
        $template = '{{ css(active ? { color: \'primary\' } : { p: \'lg\' }) }}';
        $visitor = self::getContainer()->get('ux_css.twig.node_visitor');
        $collected = [];
        $visitor->collectInto(static function (array $styles) use (&$collected): void {
            $collected[] = $styles;
        });
        $twig = self::twig();

        $twig->parse($twig->tokenize(new Source($template, 'collect.html.twig')));
        $visitor->collectInto(null);

        $this->assertSame([['color' => 'primary'], ['p' => 'lg']], $collected);
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return new TestKernel(self::CONFIG, 'test', $options['debug'] ?? true);
    }

    private function render(string $template, array $context = []): string
    {
        return self::twig()->createTemplate($template)->render($context);
    }

    private function compile(string $template): string
    {
        $twig = self::twig();
        $twig->setLoader(new ChainLoader([new ArrayLoader(['test.html.twig' => $template]), $twig->getLoader()]));

        return $twig->compileSource(new Source($template, 'test.html.twig'));
    }

    private static function twig(): Environment
    {
        return self::getContainer()->get('twig');
    }
}
