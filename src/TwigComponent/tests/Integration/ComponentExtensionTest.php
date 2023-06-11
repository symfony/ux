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
use Twig\Environment;

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
        $this->expectException(\TypeError::class);
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

    public function testTwigComponent(): void
    {
        $output = $this->renderComponent('component_a', [
            'propA' => 'prop a value',
            'propB' => 'prop b value',
        ]);

        $this->assertStringContainsString('propA: prop a value', $output);
        $this->assertStringContainsString('propB: prop b value', $output);
    }

    public function testComponentSyntaxOpenTags(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('tags/open_tag.html.twig');

        $this->assertStringContainsString('propA: 1', $output);
        $this->assertStringContainsString('propB: hello', $output);
    }

    public function testRenderTwigComponentWithSlot(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/render_with_default_slot.html.twig');

        $this->assertStringContainsString('You have a new message!', $output);
    }

    public function testRenderTwigComponentWithAttributes(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/render_with_attributes.html.twig');

        $this->assertStringContainsString('You have a new message!', $output);
        $this->assertStringContainsString('background: red', $output);
    }

    public function testRenderTwigComponentWithCustomSlot(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/render_with_custom_slot.html.twig');

        $this->assertStringContainsString('You have a new message!', $output);
        $this->assertStringContainsString('background: red', $output);
        $this->assertStringContainsString('from @fapbot', $output);
    }

    public function testRenderStaticTwigComponent(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/render_static_components.html.twig');

        $this->assertStringContainsString('Submit!', $output);
    }

    public function testRenderStaticTwigComponentWithAttributes(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/use_attribute_variables.html.twig');

        $this->assertStringContainsString('Submit!', $output);
        $this->assertStringContainsString('class="btn btn-primary"', $output);
    }

    public function testRenderStaticComponentInSubFolder(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/render_static_component_in_sub_folder.html.twig');

        $this->assertStringContainsString('Hello from a sub folder', $output);
    }

    public function testRenderNestedComponents(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/render_nested_component.html.twig');

        $this->assertStringContainsString('You have a new message from @fabot', $output);
        $this->assertStringContainsString('Go to the message', $output);
    }

    public function testPassDefaultSlotToChildComponents(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/pass_default_slot_to_child.html.twig');

        $this->assertStringContainsString('<button  class="btn">Delete User</button>', $output);
    }

    public function testCanRenderMixOfBlockAndSlot(): void
    {
        $output = self::getContainer()->get(Environment::class)->render('slot/render_mix_of_slot_and_blocks.html.twig');

        $this->assertStringContainsString('<p>You have an alert!</p>', $output);
        $this->assertStringContainsString('Hey!', $output);
        $this->assertStringContainsString('from @bob', $output);
    }

    private function renderComponent(string $name, array $data = []): string
    {
        return self::getContainer()->get(Environment::class)->render('render_component.html.twig', [
            'name' => $name,
            'data' => $data,
        ]);
    }
}
