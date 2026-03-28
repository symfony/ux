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

    public function testRadioGroupPreservesValueAndSetsChecked(): void
    {
        /** @var ComponentRenderer $renderer */
        $renderer = self::getContainer()->get('ux.twig_component.component_renderer');

        $html = $renderer->createAndRender('parent_with_radio_group', [
            'selected' => 'b',
            'attributes' => ['id' => 'dummy-live-id'],
        ]);

        // value attributes must be preserved as-is
        $this->assertStringContainsString('value="a"', $html);
        $this->assertStringContainsString('value="b"', $html);
        $this->assertStringContainsString('value="c"', $html);

        // only the matching radio gets checked
        $this->assertStringContainsString('value="b" type="radio" checked', $html);

        // non-matching radios must not get checked
        $this->assertStringNotContainsString('value="a" type="radio" checked', $html);
        $this->assertStringNotContainsString('value="c" type="radio" checked', $html);
    }

    public function testCheckboxGroupPreservesValueAndSetsChecked(): void
    {
        /** @var ComponentRenderer $renderer */
        $renderer = self::getContainer()->get('ux.twig_component.component_renderer');

        $html = $renderer->createAndRender('parent_with_checkbox_group', [
            'selected' => ['a', 'c'],
            'attributes' => ['id' => 'dummy-live-id'],
        ]);

        // value attributes must be preserved as-is
        $this->assertStringContainsString('value="a"', $html);
        $this->assertStringContainsString('value="b"', $html);
        $this->assertStringContainsString('value="c"', $html);

        // selected checkboxes ('a' and 'c') get checked; unselected ('b') does not
        $this->assertStringContainsString('value="a" type="checkbox" checked', $html);
        $this->assertStringNotContainsString('value="b" type="checkbox" checked', $html);
        $this->assertStringContainsString('value="c" type="checkbox" checked', $html);
    }

    public function testBooleanCheckboxSetsCheckedWithoutValueOverwrite(): void
    {
        /** @var ComponentRenderer $renderer */
        $renderer = self::getContainer()->get('ux.twig_component.component_renderer');

        $html = $renderer->createAndRender('parent_with_bool_checkbox', [
            'isActive' => true,
            'attributes' => ['id' => 'dummy-live-id'],
        ]);

        // checked attribute must be present (positive assertion — the fix works)
        $this->assertStringContainsString(' checked', $html);

        // pre-fix symptoms: subscriber must NOT have written value="1" (true cast) or value="" (false cast)
        $this->assertStringNotContainsString('value="1"', $html);
        $this->assertStringNotContainsString('value=""', $html);
    }
}
