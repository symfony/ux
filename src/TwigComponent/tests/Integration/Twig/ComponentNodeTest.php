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

    private function createEnvironment(string $template): Environment
    {
        /** @var Environment $environment */
        $environment = self::getContainer()->get(Environment::class);
        $templateLoader = new ArrayLoader([self::TEMPLATE => $template]);
        $environment->setLoader(new ChainLoader([$templateLoader, $environment->getLoader()]));

        return $environment;
    }
}
