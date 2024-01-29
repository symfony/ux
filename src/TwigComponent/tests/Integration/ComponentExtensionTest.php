<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Tests\Fixtures\User;
use Twig\Environment;
use Twig\Error\RuntimeError;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentExtensionTest extends KernelTestCase
{
    public function testCanRenderComponent(): void
    {
        $output = $this->renderComponent('component_a', [
            'propA' => 'prop a value',
            'propB' => 'prop b value',
        ]);

        $this->assertStringContainsString('propA: prop a value', $output);
        $this->assertStringContainsString('propB: prop b value', $output);
        $this->assertStringContainsString('service: service a value', $output);
    }

    public function testCanRenderTheSameComponentMultipleTimes(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('multi_render.html.twig');

        $this->assertStringContainsString('propA: prop a value 1', $output);
        $this->assertStringContainsString('propB: prop b value 1', $output);
        $this->assertStringContainsString('propA: prop a value 2', $output);
        $this->assertStringContainsString('propB: prop b value 2', $output);
        $this->assertStringContainsString('b value: pre-mount b value 1', $output);
        $this->assertStringContainsString('post value: value', $output);
        $this->assertStringContainsString('service: service a value', $output);
    }

    public function testCanRenderComponentWithMoreAdvancedTwigExpressions(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('flexible_component_attributes.html.twig');

        $this->assertStringContainsString('propA: A1', $output);
        $this->assertStringContainsString('propB: B1', $output);
        $this->assertStringContainsString('propA: A2', $output);
        $this->assertStringContainsString('propB: B2', $output);
        $this->assertStringContainsString('propA: A3', $output);
        $this->assertStringContainsString('propB: B3', $output);
        $this->assertStringContainsString('propA: A4', $output);
        $this->assertStringContainsString('propB: B4', $output);
        $this->assertStringContainsString('service: service a value', $output);
    }

    public function testCanNotRenderComponentWithInvalidExpressions(): void
    {
        if (Environment::MAJOR_VERSION === 2) {
            $this->expectException(\TypeError::class);
        } else {
            $this->expectException(RuntimeError::class);
        }

        self::getContainer()->get(Environment::class)->render('invalid_flexible_component.html.twig');
    }

    public function testCanCustomizeTemplateWithAttribute(): void
    {
        $output = $this->renderComponent('component_b', ['value' => 'b value 1']);

        $this->assertStringContainsString('Custom template 1', $output);
    }

    public function testCanCustomizeTemplateWithServiceTag(): void
    {
        $output = $this->renderComponent('component_d', ['value' => 'b value 1']);

        $this->assertStringContainsString('Custom template 2', $output);
    }

    public function testCanRenderComponentWithAttributes(): void
    {
        $output = $this->renderComponent('with_attributes', [
            'prop' => 'prop value 1',
            'class' => 'bar',
            'style' => 'color:red;',
            'value' => '',
            'autofocus' => true,
        ]);

        $this->assertStringContainsString('Component Content (prop value 1)', $output);
        $this->assertStringContainsString('<button class="foo bar" type="button" style="color:red;" value="" autofocus>', $output);

        $output = $this->renderComponent('with_attributes', [
            'prop' => 'prop value 2',
            'attributes' => ['class' => 'baz'],
            'type' => 'submit',
            'style' => 'color:red;',
        ]);

        $this->assertStringContainsString('Component Content (prop value 2)', $output);
        $this->assertStringContainsString('<button class="foo baz" type="submit" style="color:red;">', $output);
    }

    public function testCanSetCustomAttributesVariable(): void
    {
        $output = $this->renderComponent('custom_attributes', ['class' => 'from-custom']);

        $this->assertStringContainsString('<div class="from-custom"></div>', $output);
    }

    public function testRenderComponentWithExposedVariables(): void
    {
        $output = $this->renderComponent('with_exposed_variables');

        $this->assertStringContainsString('Prop1: prop1 value', $output);
        $this->assertStringContainsString('Prop2: prop2 value', $output);
        $this->assertStringContainsString('Prop3: prop3 value', $output);
        $this->assertStringContainsString('Method1: method1 value', $output);
        $this->assertStringContainsString('Method2: method2 value', $output);
        $this->assertStringContainsString('customMethod: customMethod value', $output);
    }

    public function testCanUseComputedMethods(): void
    {
        $output = $this->renderComponent('computed_component');

        $this->assertStringContainsString('countDirect1: 1', $output);
        $this->assertStringContainsString('countDirect2: 2', $output);
        $this->assertStringContainsString('countComputed1: 3', $output);
        $this->assertStringContainsString('countComputed2: 3', $output);
        $this->assertStringContainsString('countComputed3: 3', $output);
        $this->assertStringContainsString('propDirect: value', $output);
        $this->assertStringContainsString('propComputed: value', $output);
    }

    public function testCanDisableExposingPublicProps(): void
    {
        $output = $this->renderComponent('no_public_props');

        $this->assertStringContainsString('NoPublicProp1: default', $output);
    }

    public function testCanRenderEmbeddedComponent(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('embedded_component.html.twig');

        $this->assertStringContainsString('<caption>data table</caption>', $output);
        $this->assertStringContainsString('custom th (key)', $output);
        $this->assertStringContainsString('custom td (1)', $output);
    }

    public function testComponentWithNamespace(): void
    {
        $output = $this->renderComponent('foo:bar:baz');

        $this->assertStringContainsString('Content...', $output);
    }

    public function testRenderAnonymousComponent(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component.html.twig');

        $this->assertStringContainsString('Click me', $output);
        $this->assertStringContainsString('class="primary"', $output);
    }

    public function testRenderAnonymousComponentOverwriteProps(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_overwrite_props.html.twig');

        $this->assertStringContainsString('Click me', $output);
        $this->assertStringContainsString('class="secondary"', $output);
    }

    public function testRenderAnonymousComponentInNestedDirectory(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_nested_directory.html.twig');

        $this->assertStringContainsString('Submit', $output);
        $this->assertStringContainsString('class="primary"', $output);
    }

    public function testRenderAnonymousComponentWithNonScalarProps(): void
    {
        $user = new User('Fabien', 'test@test.com');

        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_none_scalar_prop.html.twig', ['user' => $user]);

        $this->assertStringContainsString('class="foo"', $output);
        $this->assertStringContainsString('Fabien', $output);
        $this->assertStringContainsString('test@test.com', $output);
        $this->assertStringContainsString('class variable defined? no', $output);
    }

    public function testComponentPropsOverwriteContextValue(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_with_variable_already_in_context.html.twig');

        $this->assertStringContainsString('<p>foo</p>', $output);
    }

    public function testComponentPropsWithTrailingComma(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_props_trailing_comma.html.twig');

        $this->assertStringContainsString('Hello foo, bar, and foobar', $output);
        $this->assertStringContainsString('Hello FOO, 123, and 456', $output);
    }

    public function testAttributesFunction(): void
    {
        $output = self::getContainer()->get(Environment::class)
            ->createTemplate('<div{{ attributes({class: "foo", "data-controller": "bar"}) }}/>')
            ->render()
        ;

        $this->assertSame('<div class="foo" data-controller="bar"/>', $output);
    }

    private function renderComponent(string $name, array $data = []): string
    {
        return self::getContainer()->get(Environment::class)->render('render_component.html.twig', [
            'name' => $name,
            'data' => $data,
        ]);
    }
}
