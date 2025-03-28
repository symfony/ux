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

use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Tests\Fixtures\User;
use Twig\Environment;
use Twig\Error\RuntimeError;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentExtensionTest extends KernelTestCase
{
    use ExpectDeprecationTrait;

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
        $this->expectException(\Throwable::class);

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

    public function testComponentPropsOverwriteContextValueWithInputProp(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_with_input_prop_with_same_name_in_context.html.twig');

        $this->assertStringContainsString('<p>bar</p>', $output);
    }

    public function testComponentPropsWithTrailingComma(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_props_trailing_comma.html.twig');

        $this->assertStringContainsString('Hello foo, bar, and foobar', $output);
        $this->assertStringContainsString('Hello FOO, 123, and 456', $output);
    }

    /**
     * @dataProvider renderingAttributesManuallyProvider
     */
    public function testRenderingAttributesManually(array $attributes, string $expected): void
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

    /**
     * @group legacy
     */
    public function testComponentWithClassMerge(): void
    {
        $this->expectDeprecation('Since symfony/ux-twig-component 2.20: Twig Function "cva" is deprecated; use "html_cva" from the "twig/html-extra" package (available since version 3.12) instead.');

        $output = self::getContainer()->get(Environment::class)->render('class_merge.html.twig');

        $this->assertStringContainsString('class="alert alert-red alert-lg font-semibold rounded-md dark:bg-gray-600 flex p-4"', $output);
    }

    public function testRenderingComponentWithNestedAttributes(): void
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

    /**
     * @dataProvider providePrefixedAttributesCases
     */
    public function testRenderPrefixedAttributes(string $attributes, bool $expectContains): void
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

    public function testRenderingHtmlSyntaxComponentWithNestedAttributes(): void
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

    public function testComponentWithPropsFromTemplateAndClass(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('component_with_props_from_template_and_class.html.twig');

        $this->assertStringContainsString('data-color=\'success\'', $output);
        $this->assertStringContainsString('data-size=\'lg\'', $output);
        $this->assertStringContainsString('Congrats !', $output);
    }

    public function testComponentWithConflictBetweenPropsFromTemplateAndClass(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Cannot define prop "name" in template "components/Conflict.html.twig". Property already defined in component class "Symfony\UX\TwigComponent\Tests\Fixtures\Component\Conflict"');

        self::getContainer()->get(Environment::class)->render('component_with_conflict_between_props_from_template_and_class.html.twig');
    }

    public function testComponentWithEmptyProps(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_with_empty_props.html.twig');

        $this->assertStringContainsString('I have an empty props tag', $output);
    }

    /**
     * @group legacy
     */
    public function testAnonymousComponentWithPropsOverwriteParentsProps(): void
    {
        $this->expectDeprecation('Since symfony/ux-twig-component 2.20: Twig Function "cva" is deprecated; use "html_cva" from the "twig/html-extra" package (available since version 3.12) instead.');

        $output = self::getContainer()->get(Environment::class)->render('anonymous_component_with_props_overwrite_parents_props.html.twig');

        $this->assertStringContainsString('I am an icon', $output);
        $this->assertStringNotContainsString('I am md', $output);
    }

    private function renderComponent(string $name, array $data = []): string
    {
        return self::getContainer()->get(Environment::class)->render('render_component.html.twig', [
            'name' => $name,
            'data' => $data,
        ]);
    }
}
