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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\UX\Css\CssClassList;
use Symfony\UX\Css\Cva;
use Symfony\UX\Css\Engine\CssEscaper;
use Symfony\UX\Css\Tests\Fixtures\TestKernel;
use Symfony\UX\Css\Twig\CssRuntime;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Source;

final class CvaFunctionTest extends KernelTestCase
{
    private const CONFIG = [
        'tokens' => ['colors' => ['red' => '#f00', 'blue' => '#00f'], 'radii' => ['md' => '0.375rem'], 'spacing' => ['1' => '0.25rem', '3' => '0.75rem', '5' => '1.25rem']],
        'semantic_tokens' => ['colors' => ['primary' => '{colors.red}', 'fg' => '{colors.blue}']],
    ];

    private const BUTTON = "cva(base: { rounded: 'md', px: '3' }, variants: { size: { sm: { py: '1' }, lg: { px: '5' } } }, defaultVariants: { size: 'sm' })";

    public function testApplyReturnsTheClassesOfTheChosenVariants(): void
    {
        $template = '{% set button = '.self::BUTTON." %}{{ button.apply({ size: 'lg' }) }}";

        $html = $this->render($template);
        $compiled = $this->compile($template);

        $this->assertSame('bdr_md px_5', $html);
        $this->assertStringContainsString('->compiledCva(', $compiled);
    }

    public function testAVariantChosenAtRuntimeHasItsCss(): void
    {
        $projectDir = TestKernel::temporaryDirectory();
        new Filesystem()->mkdir($projectDir.'/templates');
        $template = '{% set button = '.self::BUTTON.' %}<button class="{{ button.apply({ size: size }) }}"></button>';
        file_put_contents($projectDir.'/templates/button.html.twig', $template);
        $kernel = new TestKernel(self::CONFIG, 'test', true, $projectDir);
        $kernel->boot();

        try {
            $kernel->getContainer()->get('test.service_container')->get('ux_css.stylesheet_dumper')->update();
            $css = file_get_contents($projectDir.'/var/ux_css/styles.css');
        } finally {
            $kernel->shutdown();
            new Filesystem()->remove($projectDir);
        }

        foreach (['bdr_md', 'px_3', 'py_1', 'px_5'] as $class) {
            $this->assertStringContainsString('.'.CssEscaper::escape($class).' {', $css);
        }
    }

    public function testAConstantSetBeforeTheCallCanBeUsedAnywhereInTheConfig(): void
    {
        $template = <<<'TWIG'
            {% set base = { rounded: 'md' } %}
            {% set large = 'lg' %}
            {% set padding = '5' %}
            {% set button = cva(base: base, variants: { size: { sm: { py: '1' }, lg: { px: padding } } }, defaultVariants: { size: large }) %}
            {{- button.apply() -}}
            TWIG;

        $html = $this->render($template);

        $this->assertSame('bdr_md px_5', trim($html));
    }

    public function testPositionalArgumentsFollowTheOrderOfTheParameters(): void
    {
        $variants = "{ size: { sm: { py: '1' }, lg: { px: '5' } } }";
        $compound = "[{ size: 'lg', css: { color: 'red' } }]";
        $template = "{{ cva({ rounded: 'md' }, $variants, $compound, { size: 'lg' }).apply() }}";

        $html = $this->render($template);

        $this->assertSame('bdr_md px_5 c_red', $html);
    }

    public function testAConfigWithTooManyCombinationsIsResolvedAtRuntime(): void
    {
        $values = "{ a: { color: 'red' }, b: { color: 'blue' }, c: { color: 'primary' }, d: { color: 'fg' }, e: { px: '1' }, f: { px: '3' }, g: { px: '5' } }";
        $template = "{% set recipe = cva(variants: { one: $values, two: $values, three: $values }) %}{{ recipe.apply({ one: 'a', two: 'f', three: 'b' }) }}";

        $html = $this->render($template);
        $compiled = $this->compile($template);

        $this->assertSame('c_blue px_3', $html);
        $this->assertStringContainsString('->compiledCva(', $compiled);
        $this->assertStringNotContainsString('[\"one\",\"a\"]', $compiled);
        $this->assertMatchesRegularExpression('/->compiledCva\(.*, \[\]\)/', $compiled);
    }

    public function testInDebugAnUndeclaredPropIsARuntimeError(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('The value "xl" of the cva() variant "size" is not declared, declared values are "sm", "lg"');

        $this->render('{% set button = '.self::BUTTON." %}{{ button.apply({ size: 'xl' }) }}");
    }

    public function testTheSandboxChecksACallResolvedWhenTheTemplateCompiles(): void
    {
        $runtime = self::getContainer()->get('ux_css.twig.runtime');
        $twig = new Environment(new ArrayLoader(['sandboxed.html.twig' => '{{ '.self::BUTTON.'.apply() }}']));
        $twig->addExtension(self::getContainer()->get('ux_css.twig.extension'));
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([CssRuntime::class => static fn () => $runtime]));
        $allowedMethods = [Cva::class => ['apply'], CssClassList::class => ['__toString']];
        $twig->addExtension(new SandboxExtension(new SecurityPolicy([], ['escape'], $allowedMethods), true));

        $this->expectException(SecurityNotAllowedFunctionError::class);

        $twig->render('sandboxed.html.twig');
    }

    public static function provideInvalidConfigs(): iterable
    {
        yield 'unknown argument' => ["\n{{ cva(bse: {}).apply() }}", 'Unknown argument "bse" for function "cva(base, variants, compound_variants, default_variants)".', 2];
        yield 'unknown token in a variant' => ["\n\n{{ cva(variants: { tone: { main: { color: 'primry' } } }).apply() }}", 'Unknown colors token "primry". Did you mean "primary"?', 3];
        yield 'default with an undeclared value' => ["{{ cva(variants: { size: { sm: {} } }, defaultVariants: { size: 'lg' }).apply() }}", 'The value "lg" of the cva() default variant "size" is not declared, declared values are "sm".', 1];
        yield 'compound with an undeclared variant' => ["{{ cva(variants: { size: { sm: {} } }, compoundVariants: [{ tone: 'main', css: {} }]).apply() }}", 'The cva() compound variant "tone" is not a declared variant, declared variants are "size".', 1];
        yield 'config from a context variable' => ["\n{{ cva(base: config).apply() }}", 'The cva() config must be known when the template compiles: write it as a hash of constants, or of variables set once to a constant before the call.', 2];
        yield 'config with a ternary' => ["{{ cva(base: active ? { p: '1' } : {}).apply() }}", 'The cva() config must be known when the template compiles: write it as a hash of constants, or of variables set once to a constant before the call.', 1];
        yield 'constant set inside an if' => ["{% if active %}{% set base = { p: '1' } %}{% endif %}\n{{ cva(base: base).apply() }}", 'The cva() config must be known when the template compiles: write it as a hash of constants, or of variables set once to a constant before the call.', 2];
        yield 'set after the call' => ["{{ cva(base: base).apply() }}{% set base = { p: '1' } %}", 'The cva() config must be known when the template compiles: write it as a hash of constants, or of variables set once to a constant before the call.', 1];
        yield 'assigned twice' => ["{% set base = { p: '1' } %}{% set base = { p: '3' } %}\n\n{{ cva(base: base).apply() }}", 'The cva() config must be known when the template compiles: write it as a hash of constants, or of variables set once to a constant before the call.', 3];
        yield 'positional variants not a hash' => ["{{ cva({}, 'size').apply() }}", 'The cva() "variants" must be a hash, got "string".', 1];
        yield 'too many positional arguments' => ['{{ cva({}, {}, [], {}, {}).apply() }}', 'cva() takes at most four arguments: base, variants, compoundVariants, defaultVariants.', 1];
        yield 'the former single config hash with a variable' => ["\n{{ cva({ base: color, variants: {} }).apply() }}", 'cva() takes named arguments, like cva(base: {...}, variants: {...}).', 2];
        yield 'the former single config hash' => ["\n{{ cva({ base: { p: '1' }, variants: {} }).apply() }}", 'cva() takes named arguments, like cva(base: {...}, variants: {...}).', 2];
    }

    #[DataProvider('provideInvalidConfigs')]
    public function testInvalidConfigsAreSyntaxErrors(string $template, string $message, int $line): void
    {
        try {
            $this->compile($template);
            $this->fail('The template should not compile.');
        } catch (SyntaxError $e) {
            $this->assertSame($message, $e->getRawMessage());
            $this->assertSame($line, $e->getTemplateLine());
        }
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
