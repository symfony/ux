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

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Tests\Fixtures\User;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extra\Html\HtmlAttr\AttributeValueInterface;
use Twig\Extra\Html\HtmlAttr\MergeableInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentExtensionTest extends KernelTestCase
{
    public function testCanRenderComponent()
    {
        $output = $this->renderComponent('component_a', [
            'propA' => 'prop a value',
            'propB' => 'prop b value',
        ]);

        $this->assertStringContainsString('propA: prop a value', $output);
        $this->assertStringContainsString('propB: prop b value', $output);
        $this->assertStringContainsString('service: service a value', $output);
    }

    public function testCanRenderTheSameComponentMultipleTimes()
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

    public function testCanRenderComponentWithMoreAdvancedTwigExpressions()
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

    public function testCanNotRenderComponentWithInvalidExpressions()
    {
        $this->expectException(\Throwable::class);

        self::getContainer()->get(Environment::class)->render('invalid_flexible_component.html.twig');
    }

    public function testCanCustomizeTemplateWithAttribute()
    {
        $output = $this->renderComponent('component_b', ['value' => 'b value 1']);

        $this->assertStringContainsString('Custom template 1', $output);
    }

    public function testCanCustomizeTemplateWithServiceTag()
    {
        $output = $this->renderComponent('component_d', ['value' => 'b value 1']);

        $this->assertStringContainsString('Custom template 2', $output);
    }

    public function testCanRenderComponentWithAttributes()
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

    public function testCanSetCustomAttributesVariable()
    {
        $output = $this->renderComponent('custom_attributes', ['class' => 'from-custom']);

        $this->assertStringContainsString('<div class="from-custom"></div>', $output);
    }

    public function testRenderComponentWithExposedVariables()
    {
        $output = $this->renderComponent('with_exposed_variables');

        $this->assertStringContainsString('Prop1: prop1 value', $output);
        $this->assertStringContainsString('Prop2: prop2 value', $output);
        $this->assertStringContainsString('Prop3: prop3 value', $output);
        $this->assertStringContainsString('Method1: method1 value', $output);
        $this->assertStringContainsString('Method2: method2 value', $output);
        $this->assertStringContainsString('customMethod: customMethod value', $output);
    }

    public function testCanUseComputedMethods()
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

    public function testCanDisableExposingPublicProps()
    {
        $output = $this->renderComponent('no_public_props');

        $this->assertStringContainsString('NoPublicProp1: default', $output);
    }

    public function testCanRenderEmbeddedComponent()
    {
        $output = self::getContainer()->get(Environment::class)->render('embedded_component.html.twig');

        $this->assertStringContainsString('<caption>data table</caption>', $output);
        $this->assertStringContainsString('custom th (key)', $output);
        $this->assertStringContainsString('custom td (1)', $output);
    }

    public function testCanRenderEmbeddedComponentWithDynamicNameBuiltInLoop()
    {
        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('{% set prefix = "DynamicNameComponent" %}{% for i in 1..2 %}{% component (prefix ~ i) %}{% endcomponent %}{% endfor %}');

        $output = $template->render();

        $this->assertStringContainsString('DynamicNameComponent1 rendered', $output);
        $this->assertStringContainsString('DynamicNameComponent2 rendered', $output);
    }

    public function testThrowsWhenDynamicComponentNameDoesNotExist()
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Unknown component "DynamicNameComponent3".');

        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('{% set prefix = "DynamicNameComponent" %}{% component (prefix ~ 3) %}{% endcomponent %}');
        $template->render();
    }

    #[DataProvider('provideDynamicComponentNameExpressions')]
    public function testCanRenderEmbeddedComponentFromExpression(string $template, array $context, array $expectedComponents)
    {
        $environment = self::getContainer()->get(Environment::class);
        $output = $environment->createTemplate($template)->render($context);

        foreach ($expectedComponents as $expectedComponent) {
            $this->assertStringContainsString($expectedComponent.' rendered', $output);
        }
    }

    public static function provideDynamicComponentNameExpressions(): iterable
    {
        yield 'array index' => [
            '{% for i in 0..1 %}{% component (componentsArray[i]) %}{% endcomponent %}{% endfor %}',
            ['componentsArray' => ['DynamicNameComponent1', 'DynamicNameComponent2']],
            ['DynamicNameComponent1', 'DynamicNameComponent2'],
        ];
        yield 'component name variable' => [
            '{% component (componentNameVariable) %}{% endcomponent %}',
            ['componentNameVariable' => 'DynamicNameComponent1'],
            ['DynamicNameComponent1'],
        ];
        yield 'string variable' => [
            '{% component (stringVariable) %}{% endcomponent %}',
            ['stringVariable' => 'DynamicNameComponent2'],
            ['DynamicNameComponent2'],
        ];
    }

    public function testThrowsWhenStringVariableExpressionIsNotWrappedInParentheses()
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Unknown component "stringVariable"');

        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('{% component stringVariable %}{% endcomponent %}');
        $template->render([
            'stringVariable' => 'DynamicNameComponent2',
        ]);
    }

    public function testBareComponentNameStaysStaticWhenSameNamedVariableExists()
    {
        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('{% component DynamicNameComponent1 %}{% endcomponent %}');
        $output = $template->render([
            'DynamicNameComponent1' => 'DynamicNameComponent2',
        ]);

        $this->assertStringContainsString('DynamicNameComponent1 rendered', $output);
        $this->assertStringNotContainsString('DynamicNameComponent2 rendered', $output);
    }

    public function testThrowsWhenExpressionDoesNotEvaluateToAComponentName()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('must evaluate to a component name (string/scalar/Stringable)');
        $this->expectExceptionMessage('stdClass');

        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('{% component (obj) %}{% endcomponent %}');
        $template->render(['obj' => new \stdClass()]);
    }

    public function testCanRenderSelfClosingDynamicComponentWithStaticIs()
    {
        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('<twig:component is="DynamicNameComponent1" />');
        $output = $template->render();

        $this->assertStringContainsString('DynamicNameComponent1 rendered', $output);
    }

    public function testCanRenderSelfClosingDynamicComponentWithDynamicIs()
    {
        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('<twig:component :is="componentName" />');
        $output = $template->render(['componentName' => 'DynamicNameComponent1']);

        $this->assertStringContainsString('DynamicNameComponent1 rendered', $output);
    }

    public function testCanRenderPairedDynamicComponentWithStaticIs()
    {
        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('<twig:component is="DynamicNameComponent1">content</twig:component>');
        $output = $template->render();

        $this->assertStringContainsString('DynamicNameComponent1 rendered', $output);
    }

    public function testCanRenderPairedDynamicComponentWithDynamicIs()
    {
        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('<twig:component :is="componentName">content</twig:component>');
        $output = $template->render(['componentName' => 'DynamicNameComponent1']);

        $this->assertStringContainsString('DynamicNameComponent1 rendered', $output);
    }

    public function testCanRenderDynamicComponentWithDynamicIsInLoop()
    {
        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('{% set prefix = "DynamicNameComponent" %}{% for i in 1..2 %}<twig:component :is="prefix ~ i" />{% endfor %}');
        $output = $template->render();

        $this->assertStringContainsString('DynamicNameComponent1 rendered', $output);
        $this->assertStringContainsString('DynamicNameComponent2 rendered', $output);
    }

    public function testCanRenderDynamicComponentWithDynamicIsFromArray()
    {
        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('{% for i in 0..1 %}<twig:component :is="names[i]" />{% endfor %}');
        $output = $template->render(['names' => ['DynamicNameComponent1', 'DynamicNameComponent2']]);

        $this->assertStringContainsString('DynamicNameComponent1 rendered', $output);
        $this->assertStringContainsString('DynamicNameComponent2 rendered', $output);
    }

    public function testCanRenderDynamicComponentWithPropsViaStaticIs()
    {
        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('<twig:component is="component_a" propA="A" propB="B" />');
        $output = $template->render();

        $this->assertStringContainsString('propA: A', $output);
        $this->assertStringContainsString('propB: B', $output);
    }

    public function testCanRenderDynamicComponentWithPropsViaDynamicIs()
    {
        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('<twig:component :is="name" propA="A" propB="B" />');
        $output = $template->render(['name' => 'component_a']);

        $this->assertStringContainsString('propA: A', $output);
        $this->assertStringContainsString('propB: B', $output);
    }

    public function testThrowsWhenDynamicComponentHtmlSyntaxNameDoesNotExist()
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Unknown component "NonExistent"');

        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('<twig:component :is="name" />');
        $template->render(['name' => 'NonExistent']);
    }

    public function testCanRenderDynamicComponentInsideRegularComponent()
    {
        $environment = self::getContainer()->get(Environment::class);
        $template = $environment->createTemplate('<twig:BasicComponent><twig:component :is="name" /></twig:BasicComponent>');
        $output = $template->render(['name' => 'DynamicNameComponent1']);

        $this->assertStringContainsString('DynamicNameComponent1 rendered', $output);
    }

    public function testComponentWithNamespace()
    {
        $output = $this->renderComponent('foo:bar:baz');

        $this->assertStringContainsString('Content...', $output);
    }

    public function testRenderAnonymousComponent()
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component.html.twig');

        $this->assertStringContainsString('Click me', $output);
        $this->assertStringContainsString('class="primary"', $output);
    }

    public function testRenderAnonymousComponentOverwriteProps()
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_overwrite_props.html.twig');

        $this->assertStringContainsString('Click me', $output);
        $this->assertStringContainsString('class="secondary"', $output);
    }

    public function testRenderAnonymousComponentInNestedDirectory()
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_nested_directory.html.twig');

        $this->assertStringContainsString('Submit', $output);
        $this->assertStringContainsString('class="primary"', $output);
    }

    public function testRenderAnonymousComponentWithNonScalarProps()
    {
        $user = new User('Fabien', 'test@test.com');

        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_none_scalar_prop.html.twig', ['user' => $user]);

        $this->assertStringContainsString('class="foo"', $output);
        $this->assertStringContainsString('Fabien', $output);
        $this->assertStringContainsString('test@test.com', $output);
        $this->assertStringContainsString('class variable defined? no', $output);
    }

    public function testRenderComponentTagWithNullsafeProps()
    {
        if (Environment::VERSION_ID < 32300) {
            $this->markTestSkipped('The "?." operator requires Twig 3.23+.');
        }

        $city = new \stdClass();
        $city->organisation = null;

        $item = new \stdClass();
        $item->city = $city;
        $item->title = 'Title';

        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_nullsafe_props.html.twig', [
            'item' => $item,
        ]);

        $this->assertSame(3, substr_count($output, '- Title'));
    }

    public function testComponentPropsOverwriteContextValue()
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_with_variable_already_in_context.html.twig');

        $this->assertStringContainsString('<p>foo</p>', $output);
    }

    public function testComponentPropsOverwriteContextValueWithInputProp()
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_with_input_prop_with_same_name_in_context.html.twig');

        $this->assertStringContainsString('<p>bar</p>', $output);
    }

    public function testComponentPropWithoutDefaultAcceptsNullValue()
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_with_null_props.html.twig');

        $this->assertStringContainsString('<p>required: NULL</p>', $output);
    }

    public function testComponentPropWithoutDefaultAndWithoutValueThrows()
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Prop "required" should be defined in "components/NullableProps.html.twig" at line 1.');

        self::getContainer()->get(Environment::class)->render('anonymous_component_with_missing_required_prop.html.twig');
    }

    public function testComponentPropWithDefaultKeepsNullValueExplicitlyPassed()
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_with_null_props.html.twig');

        $this->assertStringContainsString('<p>withDefault: NULL</p>', $output);
    }

    public function testComponentPropWithDefaultIgnoresNullValueFromContext()
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_with_null_variable_already_in_context.html.twig');

        $this->assertStringContainsString('<p>withDefault: default</p>', $output);
    }

    public function testComponentClassPropertyWithDefaultKeepsNullValueExplicitlyPassed()
    {
        $output = self::getContainer()->get(Environment::class)->render('class_component_with_null_props.html.twig');

        $this->assertStringContainsString('<p>property: NULL</p>', $output);
        $this->assertStringContainsString('<p>mounted: NULL</p>', $output);
    }

    public function testComponentClassPropertyWithDefaultKeepsItWhenPropIsNotPassed()
    {
        $output = self::getContainer()->get(Environment::class)->render('class_component_with_omitted_props.html.twig');

        $this->assertStringContainsString('<p>property: default</p>', $output);
        $this->assertStringContainsString('<p>mounted: default</p>', $output);
    }

    public function testComponentPropsWithTrailingComma()
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_props_trailing_comma.html.twig');

        $this->assertStringContainsString('Hello foo, bar, and foobar', $output);
        $this->assertStringContainsString('Hello FOO, 123, and 456', $output);
    }

    #[DataProvider('renderingAttributesManuallyProvider')]
    public function testRenderingAttributesManually(array $attributes, string $expected)
    {
        $actual = trim($this->renderComponent('RenderAttributes', $attributes));

        $this->assertSame($expected, trim($actual));
    }

    public static function renderingAttributesManuallyProvider(): iterable
    {
        yield [
            ['class' => 'block'],
            <<<HTML
                <div
                    foo=""
                    bar="default"
                    baz="default "
                    qux=" default"
                     class="block"
                />
                HTML,
        ];

        yield [
            [
                'class' => 'block',
                'foo' => 'value',
                'bar' => 'value',
                'baz' => 'value',
                'qux' => 'value',
            ],
            <<<HTML
                <div
                    foo="value"
                    bar="value"
                    baz="default value"
                    qux="value default"
                     class="block"
                />
                HTML,
        ];
    }

    public function testRenderingComponentWithNestedAttributes()
    {
        $output = $this->renderComponent('NestedAttributes');

        $this->assertSame(
            <<<HTML
                <main>
                    <div>
                        <span>
                            <div/>

                        </span>
                    </div>
                </main>
                HTML,
            trim($output)
        );

        $output = $this->renderComponent('NestedAttributes', [
            'class' => 'foo',
            'title:class' => 'bar',
            'title:span:class' => 'baz',
        ]);

        $this->assertSame(
            <<<HTML
                <main class="foo">
                    <div class="bar">
                        <span class="baz">
                            <div/>

                        </span>
                    </div>
                </main>
                HTML,
            trim($output)
        );
    }

    #[DataProvider('providePrefixedAttributesCases')]
    public function testRenderPrefixedAttributes(string $attributes, bool $expectContains)
    {
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);
        $template = $twig->createTemplate(\sprintf('<twig:PrefixedAttributes %s/>', $attributes));

        if ($expectContains) {
            self::assertStringContainsString($attributes, trim($template->render()));

            return;
        }

        self::assertStringNotContainsString($attributes, trim($template->render()));
    }

    /**
     * @return iterable<array{0: string, 1: bool}>
     */
    public static function providePrefixedAttributesCases(): iterable
    {
        // General
        yield ['x:men', false]; // Nested
        yield ['x:men="u"', false];  // Nested
        yield ['x-men', true];
        yield ['x-men="u"', true];

        // AlpineJS
        yield ['x-click="count++"', true];
        yield ['x-on:click="count++"', true];
        yield ['@click="open"', true];
        // Not AlpineJS
        yield ['z-click="count++"', true];
        yield ['z-on:click="count++"', false]; // Nested

        // Stencil
        yield ['onClick="count++"', true];
        yield ['@onClick="count++"', true];

        // VueJs
        yield ['v-model="message"', true];
        yield ['v-bind:id="dynamicId"', true];
        yield ['v-bind:id', true];
        yield ['@submit.prevent="onSubmit"', true];
        // Not VueJs
        yield ['z-model="message"', true];
        yield ['z-bind:id="dynamicId"', false]; // Nested
        yield ['z-bind:id', false]; // Nested
    }

    public function testRenderingHtmlSyntaxComponentWithNestedAttributes()
    {
        $output = self::getContainer()
            ->get(Environment::class)
            ->createTemplate('<twig:NestedAttributes />')
            ->render()
        ;

        $this->assertSame(
            <<<HTML
                <main>
                    <div>
                        <span>
                            <div/>

                        </span>
                    </div>
                </main>
                HTML,
            trim($output)
        );

        $output = self::getContainer()
            ->get(Environment::class)
            ->createTemplate('<twig:NestedAttributes class="foo" title:class="bar" title:span:class="baz" inner:class="foo" inner:@class="qux" @class="vex" />')
            ->render()
        ;

        $this->assertSame(
            <<<HTML
                <main class="foo" @class="vex">
                    <div class="bar">
                        <span class="baz">
                            <div class="foo" @class="qux"/>

                        </span>
                    </div>
                </main>
                HTML,
            trim($output)
        );
    }

    public function testComponentWithPropsFromTemplateAndClass()
    {
        $output = self::getContainer()->get(Environment::class)->render('component_with_props_from_template_and_class.html.twig');

        $this->assertStringContainsString('data-color=\'success\'', $output);
        $this->assertStringContainsString('data-size=\'lg\'', $output);
        $this->assertStringContainsString('Congrats !', $output);
    }

    public function testComponentWithConflictBetweenPropsFromTemplateAndClass()
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Cannot define prop "name" in template "components/Conflict.html.twig". Property already defined in component class "Symfony\UX\TwigComponent\Tests\Fixtures\Component\Conflict"');

        self::getContainer()->get(Environment::class)->render('component_with_conflict_between_props_from_template_and_class.html.twig');
    }

    public function testComponentWithConflictBetweenNullPropFromTemplateAndClass()
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Cannot define prop "name" in template "components/NullableConflict.html.twig". Property already defined in component class "Symfony\\UX\\TwigComponent\\Tests\\Fixtures\\Component\\NullableConflict"');

        self::getContainer()->get(Environment::class)->render('component_with_conflict_between_null_prop_from_template_and_class.html.twig');
    }

    public function testComponentWithEmptyProps()
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_with_empty_props.html.twig');

        $this->assertStringContainsString('I have an empty props tag', $output);
    }

    #[DataProvider('provideUnsafeAttributes')]
    public function testHtmlSyntaxEscapesAttributeValues(string $input)
    {
        $output = self::getContainer()->get(Environment::class)->render(
            'anonymous_component_with_html_syntax.html.twig',
            ['input' => $input]
        );

        $this->assertStringNotContainsString('<script', $output);
        $this->assertStringContainsString('&lt;scr', $output);
    }

    #[DataProvider('provideUnsafeAttributes')]
    public function testDynamicSyntaxEscapesAttributeValues(string $input)
    {
        $output = self::getContainer()->get(Environment::class)->render(
            'anonymous_component_with_dynamic_syntax.html.twig',
            ['input' => $input]
        );

        $this->assertStringNotContainsString('<script', $output);
        $this->assertStringContainsString('&lt;scr', $output);
    }

    public static function provideUnsafeAttributes(): iterable
    {
        return array_map(static fn ($s) => (array) $s, [
            '"><script>alert("XSS")</script>',
            '\"><script>alert(\"XSS\")</script>',
            "'><script>alert(\"XSS\")</script>",
            "\'><script>alert(\"XSS\")</script>",
        ]);
    }

    public function testHigherOrderComponentWithAttributeDefaults()
    {
        $output = self::getContainer()->get(Environment::class)->render('higher_order_component.html.twig');

        // Test base Modal component
        $this->assertStringContainsString('<div class="modal">', $output);
        $this->assertStringContainsString('Simple modal content', $output);

        // Test Modal:Confirm adds confirmation buttons with default text and default class
        $this->assertStringContainsString('Are you sure you want to delete this item?', $output);
        $this->assertStringContainsString('<button type="button" class="btn-secondary">Cancel</button>', $output);
        $this->assertStringContainsString('<button type="submit" class="btn-primary">Confirm</button>', $output);
        $this->assertStringContainsString('<div class="modal modal-confirm">', $output);

        // Test Modal:Confirm with custom button text
        $this->assertStringContainsString('This action cannot be undone.', $output);
        $this->assertStringContainsString('<button type="button" class="btn-secondary">No, keep it</button>', $output);
        $this->assertStringContainsString('<button type="submit" class="btn-primary">Yes, delete it</button>', $output);

        // Test Modal:Confirm passes attributes through to base Modal
        $this->assertStringContainsString('data-controller="modal"', $output);
        $this->assertStringContainsString('id="delete-modal"', $output);
        $this->assertStringContainsString('Confirm deletion?', $output);
    }

    public function testPropsAreRemovedFromAttributesInSingleCall()
    {
        $output = $this->renderComponent('PropsAndAttributesSeparation', [
            'propA' => 'valueA',
            'propB' => 'valueB',
            'propC' => 'valueC',
            'class' => 'my-class',
            'data-extra' => 'extra-value',
        ]);

        // Props should be available as template variables
        $this->assertStringContainsString('propA=valueA', $output);
        $this->assertStringContainsString('propB=valueB', $output);
        $this->assertStringContainsString('propC=valueC', $output);

        // Props should NOT be in attributes anymore
        $this->assertStringContainsString('has-propA-attr=no', $output);
        $this->assertStringContainsString('has-propB-attr=no', $output);
        $this->assertStringContainsString('has-propC-attr=no', $output);

        // Non-prop attributes should still be in attributes
        $this->assertStringContainsString('has-class-attr=yes', $output);
        $this->assertStringContainsString('has-extra-attr=yes', $output);

        // Props should be defined in context
        $this->assertStringContainsString('propA-in-context=yes', $output);
        $this->assertStringContainsString('propB-in-context=yes', $output);
        $this->assertStringContainsString('propC-in-context=yes', $output);

        // Non-prop attributes should be rendered in the div
        $this->assertStringContainsString('class="my-class"', $output);
        $this->assertStringContainsString('data-extra="extra-value"', $output);
    }

    /**
     * Test that extra attributes (non-props) are correctly cleaned from context.
     *
     * This is a regression test for the optimization that iterates directly over
     * attributes->all() instead of iterating over the entire context.
     */
    public function testExtraAttributesAreCleanedFromContext()
    {
        $output = $this->renderComponent('PropsAndAttributesSeparation', [
            'propA' => 'A',
            'propB' => 'B',
            'class' => 'test-class',
            'data-extra' => 'test-extra',
            'id' => 'test-id',
        ]);

        // Props should be available
        $this->assertStringContainsString('propA=A', $output);
        $this->assertStringContainsString('propB=B', $output);
        $this->assertStringContainsString('propC=default', $output);

        // All non-prop attributes should be in the attributes object
        $this->assertStringContainsString('class="test-class"', $output);
        $this->assertStringContainsString('data-extra="test-extra"', $output);
        $this->assertStringContainsString('id="test-id"', $output);
    }

    /**
     * Test that props with default values work correctly when not provided.
     */
    public function testPropsWithDefaultValuesWhenNotProvided()
    {
        $output = $this->renderComponent('PropsAndAttributesSeparation', [
            'propA' => 'A',
            'propB' => 'B',
            // propC is not provided, should use default
            'class' => 'some-class',
        ]);

        $this->assertStringContainsString('propA=A', $output);
        $this->assertStringContainsString('propB=B', $output);
        $this->assertStringContainsString('propC=default', $output);

        // propC should not be in attributes
        $this->assertStringContainsString('has-propC-attr=no', $output);
    }

    /**
     * Test that attributes passed to a component do not leak into template context.
     *
     * This is a regression test for the optimization that directly iterates over
     * attributes->all() to unset context variables, ensuring non-prop attributes
     * are properly cleaned from the context.
     */
    public function testAttributesDoNotLeakToTemplateContext()
    {
        $output = $this->renderComponent('AttributesNotLeakingToContext', [
            'name' => 'test-name',
            'class' => 'my-class',
            'style' => 'color: red;',
            'data-foo' => 'bar',
        ]);

        // The prop should be available
        $this->assertStringContainsString('name=test-name', $output);

        // The attributes should be rendered in the HTML
        $this->assertStringContainsString('class="my-class"', $output);
        $this->assertStringContainsString('style="color: red;"', $output);
        $this->assertStringContainsString('data-foo="bar"', $output);

        // But the attribute names should NOT be defined as template variables
        $this->assertStringContainsString('class-var-defined=no', $output);
        $this->assertStringContainsString('style-var-defined=no', $output);
        $this->assertStringContainsString('data_foo-var-defined=no', $output);
    }

    public function testPropsWithHtmlAttrMergeFilter()
    {
        if (!interface_exists(AttributeValueInterface::class)) {
            $this->markTestSkipped('Test requires Twig HTML extra >= 3.24.');
        }

        $output = self::getContainer()->get(Environment::class)->render('html_attr_merge.html.twig');

        $this->assertStringContainsString('class="primary"', $output);
        $this->assertStringContainsString('data-action="click-&gt;dialog#open mouseenter-&gt;tooltip#show mouseleave-&gt;tooltip#hide focus-&gt;tooltip#show blur-&gt;tooltip#hide"', $output);
        // When no HTML Attr Type has been defined, the very last takes precedence
        $this->assertStringContainsString('data-no-html-attr-type="trigger"', $output);
        $this->assertStringContainsString('data-html-attr-type-cst="dialog, trigger"', $output);
    }

    public function testDefaultsWithMergeableFilter()
    {
        if (!interface_exists(MergeableInterface::class)) {
            $this->markTestSkipped('Test requires Twig HTML extra >= 3.24.');
        }

        $output = self::getContainer()->get(Environment::class)->render('mergeable_tokens.html.twig');

        // "data-foo" is not one of the special (class/data-controller/data-action) keys:
        // without the MergeableInterface routing the caller value would simply override the
        // default ("c"). Getting the merged "a b c" proves the protocol runs for any key,
        // across the full component render.
        $this->assertStringContainsString('data-foo="a b c"', $output);
    }

    private function renderComponent(string $name, array $data = []): string
    {
        return self::getContainer()->get(Environment::class)->render('render_component.html.twig', [
            'name' => $name,
            'data' => $data,
        ]);
    }
}
