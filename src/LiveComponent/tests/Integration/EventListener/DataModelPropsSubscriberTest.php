<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Integration\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Symfony\UX\TwigComponent\ComponentRenderer;

final class DataModelPropsSubscriberTest extends KernelTestCase
{
    use LiveComponentTestHelper;

    public function testDataModelPropsAreSharedToChild()
    {
        /** @var ComponentRenderer $renderer */
        $renderer = self::getContainer()->get('ux.twig_component.component_renderer');

        $html = $renderer->createAndRender('parent_form_component', [
            // content is mapped down to "value" in a child component
            'content' => 'Hello data-model!',
            'content2' => 'Value for second child',
            // Normally createAndRender is always called from within a Template via the ComponentExtension.
            // To avoid that the DeterministicTwigIdCalculator complains that there's no Template
            // to base the live id on, we'll add this dummy one, so it gets skipped.
            'attributes' => ['id' => 'dummy-live-id'],
        ]);

        $this->assertStringContainsString('<textarea data-model="content:value">Hello data-model!</textarea>', $html);
        $this->assertStringContainsString('<textarea data-model="content2:value">Value for second child</textarea>', $html);
    }

    public function testDataModelPropsAreAvailableInEmbeddedComponents()
    {
        $templateName = 'components/parent_component_data_model.html.twig';
        $obscuredName = '684c45bf85d3461dbe587407892e59d8';
        $this->addTemplateMap($obscuredName, $templateName);

        /** @var ComponentRenderer $renderer */
        $renderer = self::getContainer()->get('ux.twig_component.component_renderer');

        $html = $renderer->createAndRender('parent_component_data_model', [
            'attributes' => ['id' => 'dummy-live-id'],
        ]);

        $this->assertStringContainsString('<textarea data-model="content">default content on mount</textarea>', $html);
        $this->assertStringContainsString('<input data-model="content" value="default content on mount" />', $html);
    }

    public function testDataModelOnRadioAndCheckboxInputs()
    {
        /** @var ComponentRenderer $renderer */
        $renderer = self::getContainer()->get('ux.twig_component.component_renderer');

        $html = $renderer->createAndRender('parent_component_data_model_inputs', [
            'attributes' => ['id' => 'dummy-live-id'],
        ]);

        // radio: each input keeps its own "value" (not overwritten with the prop value),
        // and only the one matching the "choice" prop ("b") is checked
        $this->assertStringContainsString('<input data-model="choice" type="radio" value="a" />', $html);
        $this->assertStringContainsString('<input data-model="choice" type="radio" value="b" checked />', $html);
        $this->assertStringContainsString('<input data-model="choice" type="radio" value="c" />', $html);

        // checkbox group bound to an array prop: per-input "value" is kept and only the
        // values contained in the array ("paris") are checked. The "selected[]" notation
        // (a JS-only convention) must not crash the PropertyAccessor.
        $this->assertStringContainsString('<input data-model="selected[]" type="checkbox" value="paris" checked />', $html);
        $this->assertStringContainsString('<input data-model="selected[]" type="checkbox" value="lyon" />', $html);

        // boolean checkbox whose "type" is hardcoded in the child template (so it never
        // appears in the props): the bool prop drives "checked" without overwriting "value"
        $this->assertStringContainsString('<input type="checkbox" data-model="active" checked />', $html);

        // a regular text input is unaffected: its value is still set from the prop
        $this->assertStringContainsString('<input data-model="name" type="text" value="John" />', $html);
    }
}
