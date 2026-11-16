<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Integration\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\TwigFunction;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class ComponentNodeTest extends KernelTestCase
{
    private const TEMPLATE = 'component_node.html.twig';

    #[DataProvider('provideStaticTemplates')]
    public function testStaticNameAndPropsAreCompiledWithoutRuntimeConversion(string $template, array $context, string $expected): void
    {
        $environment = $this->createEnvironment($template);
        $source = $environment->getLoader()->getSourceContext(self::TEMPLATE);

        $code = $environment->compileSource($source);
        $output = $environment->render(self::TEMPLATE, $context);

        $this->assertStringNotContainsString('is_scalar', $code);
        $this->assertStringNotContainsString('CoreExtension::toArray', $code);
        $this->assertSame($expected, trim($output));
    }

    public static function provideStaticTemplates(): iterable
    {
        yield 'quoted name without props' => [
            "{% component 'JustAttributes' %}{% endcomponent %}",
            [],
            '<div/>',
        ];
        yield 'quoted name with literal props' => [
            "{% component 'JustAttributes' with {class: 'foo'} %}{% endcomponent %}",
            [],
            '<div class="foo"/>',
        ];
        yield 'parenthesized quoted name' => [
            "{% component ('JustAttributes') with {class: 'foo'} %}{% endcomponent %}",
            [],
            '<div class="foo"/>',
        ];
        yield 'bare name with literal props' => [
            "{% component JustAttributes with {class: 'foo'} %}{% endcomponent %}",
            [],
            '<div class="foo"/>',
        ];
        yield 'literal props holding a variable' => [
            '{% component "JustAttributes" with {class: class} %}{% endcomponent %}',
            ['class' => 'foo'],
            '<div class="foo"/>',
        ];
        yield 'literal props holding a spread' => [
            "{% component 'JustAttributes' with {class: 'foo', ...extra} %}{% endcomponent %}",
            ['extra' => ['id' => 'bar']],
            '<div class="foo" id="bar"/>',
        ];
        yield 'HTML syntax' => [
            '<twig:JustAttributes class="foo" id="{{ id }}"></twig:JustAttributes>',
            ['id' => 'bar'],
            '<div class="foo" id="bar"/>',
        ];
    }

    #[DataProvider('provideDynamicNameTemplates')]
    public function testDynamicNameIsCheckedAtRuntime(string $template, array $context, string $expected): void
    {
        $environment = $this->createEnvironment($template);
        $source = $environment->getLoader()->getSourceContext(self::TEMPLATE);

        $code = $environment->compileSource($source);
        $output = $environment->render(self::TEMPLATE, $context);

        $this->assertStringContainsString('is_scalar', $code);
        $this->assertSame($expected, trim($output));
    }

    public static function provideDynamicNameTemplates(): iterable
    {
        yield 'variable' => [
            "{% component (name) with {class: 'foo'} %}{% endcomponent %}",
            ['name' => 'JustAttributes'],
            '<div class="foo"/>',
        ];
        yield 'concatenation' => [
            "{% component ('Just' ~ suffix) with {class: 'foo'} %}{% endcomponent %}",
            ['suffix' => 'Attributes'],
            '<div class="foo"/>',
        ];
    }

    #[DataProvider('provideDynamicPropsTemplates')]
    public function testDynamicPropsAreConvertedAtRuntime(string $template, array $context, string $expected): void
    {
        $environment = $this->createEnvironment($template);
        $source = $environment->getLoader()->getSourceContext(self::TEMPLATE);

        $code = $environment->compileSource($source);
        $output = $environment->render(self::TEMPLATE, $context);

        $this->assertStringContainsString('CoreExtension::toArray', $code);
        $this->assertSame($expected, trim($output));
    }

    public static function provideDynamicPropsTemplates(): iterable
    {
        yield 'array variable' => [
            "{% component 'JustAttributes' with props %}{% endcomponent %}",
            ['props' => ['class' => 'foo']],
            '<div class="foo"/>',
        ];
        yield 'traversable variable' => [
            "{% component 'JustAttributes' with props %}{% endcomponent %}",
            ['props' => new \ArrayIterator(['class' => 'foo'])],
            '<div class="foo"/>',
        ];
        yield 'filtered literal' => [
            "{% component 'JustAttributes' with {class: 'foo'}|merge({id: 'bar'}) %}{% endcomponent %}",
            [],
            '<div class="foo" id="bar"/>',
        ];
    }

    #[DataProvider('provideOuterBlocksTemplates')]
    public function testOuterBlocksAreConvertedOnlyForAContentThatMayReadThem(string $template, array $context, int $conversions, int $passThroughs, string $expected): void
    {
        $environment = $this->createEnvironment($template);
        $source = $environment->getLoader()->getSourceContext(self::TEMPLATE);

        $code = $environment->compileSource($source);
        $output = $environment->render(self::TEMPLATE, $context);

        $this->assertSame($conversions, substr_count($code, '->convert('));
        $this->assertSame($passThroughs, substr_count($code, '->passThrough('));
        $this->assertSame($expected, trim($output));
    }

    public static function provideOuterBlocksTemplates(): iterable
    {
        yield 'content without outerBlocks' => [
            '<twig:OuterBlocksPassThrough:Box>content</twig:OuterBlocksPassThrough:Box>',
            [],
            0,
            1,
            '<box>content</box>',
        ];
        yield 'content reading outerBlocks' => [
            '<twig:OuterBlocksPassThrough:Box>[{{ block(outerBlocks.missing) }}]</twig:OuterBlocksPassThrough:Box>',
            [],
            1,
            0,
            '<box>[]</box>',
        ];
        yield 'content reading the whole context' => [
            "<twig:OuterBlocksPassThrough:Box>{{ _context.outerBlocks is defined ? 'yes' }}</twig:OuterBlocksPassThrough:Box>",
            [],
            1,
            0,
            '<box>yes</box>',
        ];
        yield 'content rendering a block by a runtime name' => [
            '<twig:OuterBlocksPassThrough:Box>{{ block(name) }}{% block foo %}foo{% endblock %}</twig:OuterBlocksPassThrough:Box>',
            ['name' => 'foo'],
            1,
            0,
            '<box>foo</box>',
        ];
        yield 'content rendering a block by a constant name' => [
            "<twig:OuterBlocksPassThrough:Box>{{ block('foo') }}{% block foo %}foo{% endblock %}</twig:OuterBlocksPassThrough:Box>",
            [],
            0,
            1,
            '<box>foo</box>',
        ];
        yield 'outerBlocks read only by a nested component' => [
            '<twig:OuterBlocksPassThrough:Box><twig:OuterBlocksPassThrough:Box>[{{ block(outerBlocks.missing) }}]</twig:OuterBlocksPassThrough:Box></twig:OuterBlocksPassThrough:Box>',
            [],
            1,
            1,
            "<box><box>[]</box>\n</box>",
        ];
    }

    public function testOuterBlocksAreConvertedForAContentCallingAFunctionThatReceivesTheContext(): void
    {
        $template = '{% block foo %}foo{% endblock %}|<twig:OuterBlocksPassThrough:Box><twig:OuterBlocksPassThrough:Show :name="outer_block_name(\'foo\')">content</twig:OuterBlocksPassThrough:Show></twig:OuterBlocksPassThrough:Box>';
        $environment = $this->createEnvironment($template);
        $outerBlockName = static fn (array $context, string $name): string => $context['outerBlocks']->$name();
        $environment->addFunction(new TwigFunction('outer_block_name', $outerBlockName, ['needs_context' => true]));
        $source = $environment->getLoader()->getSourceContext(self::TEMPLATE);

        $code = $environment->compileSource($source);
        $output = $environment->render(self::TEMPLATE);

        $this->assertSame(1, substr_count($code, '->convert('));
        $this->assertSame(1, substr_count($code, '->passThrough('));
        $this->assertSame("foo|<box><show>foo|content</show>\n</box>", trim($output));
    }

    private function createEnvironment(string $template): Environment
    {
        /** @var Environment $environment */
        $environment = self::getContainer()->get(Environment::class);
        $templateLoader = new ArrayLoader([self::TEMPLATE => $template]);
        $environment->setLoader(new ChainLoader([$templateLoader, $environment->getLoader()]));

        return $environment;
    }
}
