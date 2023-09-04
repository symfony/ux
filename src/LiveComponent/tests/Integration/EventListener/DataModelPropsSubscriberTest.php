<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Symfony\UX\TwigComponent\ComponentRenderer;

final class DataModelPropsSubscriberTest extends KernelTestCase
{
    use LiveComponentTestHelper;

    public function testDataModelPropsAreSharedToChild(): void
    {
        $this->fakeSession();

        /** @var ComponentRenderer $renderer */
        $renderer = self::getContainer()->get('ux.twig_component.component_renderer');

        $html = $renderer->createAndRender('parent_form_component', [
            // content is mapped down to "value" in a child component
            'content' => 'Hello data-model!',
            'content2' => 'Value for second child',
            // Normally createAndRender is always called from within a Template via the ComponentExtension.
            // To avoid that the DeterministicTwigIdCalculator complains that there's no Template
            // to base the live id on, we'll add this dummy one, so it gets skipped.
            'attributes' => ['data-live-id' => 'dummy-live-id'],
        ]);

        $this->assertStringContainsString('<textarea data-model="content:value">Hello data-model!</textarea>', $html);
        $this->assertStringContainsString('<textarea data-model="content2:value">Value for second child</textarea>', $html);
    }

    public function testDataModelPropsAreAvailableInEmbeddedComponents(): void
    {
        $this->fakeSession();

        $templateName = 'components/parent_component_data_model.html.twig';
        $obscuredName = '684c45bf85d3461dbe587407892e59d8';
        $this->addTemplateMap($obscuredName, $templateName);

        /** @var ComponentRenderer $renderer */
        $renderer = self::getContainer()->get('ux.twig_component.component_renderer');

        $html = $renderer->createAndRender('parent_component_data_model', [
            'attributes' => ['data-live-id' => 'dummy-live-id'],
        ]);

        $this->assertStringContainsString('<textarea data-model="content">default content on mount</textarea>', $html);
        $this->assertStringContainsString('<input data-model="content" value="default content on mount" />', $html);
    }

    private function fakeSession(): void
    {
        // work around so that a session is available so CSRF doesn't fail
        $session = self::getContainer()->get('session.factory')->createSession();
        $request = Request::create('/');
        $request->setSession($session);
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);
    }
}
