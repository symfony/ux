<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Kit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Toolkit\Kit\KitContextRunner;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentTemplateFinder;
use Symfony\UX\TwigComponent\ComponentTemplateFinderInterface;

class KitContextRunnerTest extends KernelTestCase
{
    public function testRunForKitShouldConfigureThenResetServices(): void
    {
        $twig = self::getContainer()->get('twig');
        $initialTwigLoader = $twig->getLoader();

        $componentFactory = self::getContainer()->get('ux.twig_component.component_factory');
        $initialComponentFactoryState = $this->extractComponentFactoryState($componentFactory);
        $this->assertInstanceOf(ComponentTemplateFinder::class, $initialComponentFactoryState['componentTemplateFinder']);
        $this->assertIsArray($initialComponentFactoryState['config']);

        $executed = false;
        $kitContextRunner = self::getContainer()->get('ux_toolkit.kit.kit_context_runner');
        $kitContextRunner->runForKit(self::getContainer()->get('ux_toolkit.registry.local')->getKit('shadcn'), function () use (&$executed, $twig, $initialTwigLoader, $componentFactory, $initialComponentFactoryState) {
            $executed = true;

            $this->assertNotEquals($initialTwigLoader, $twig->getLoader(), 'The Twig loader must be different in this current kit-aware context.');
            $this->assertNotEquals($initialComponentFactoryState, $this->extractComponentFactoryState($componentFactory), 'The ComponentFactory state must be different in this current kit-aware context.');

            $template = $twig->createTemplate('<twig:AspectRatio ratio="{{ 16 / 9 }}">Hello world</twig:AspectRatio>');
            $renderedTemplate = $template->render();

            $this->assertNotEmpty($renderedTemplate);
            $this->assertStringContainsString('Hello world', $renderedTemplate);
            $this->assertStringContainsString('style="aspect-ratio:', $renderedTemplate);
        });
        $this->assertTrue($executed, \sprintf('The callback passed to %s::runForKit() has not been executed.', KitContextRunner::class));

        $this->assertEquals($initialTwigLoader, $twig->getLoader(), 'The Twig loader must be back to its original implementation.');
        $this->assertEquals($initialComponentFactoryState, $this->extractComponentFactoryState($componentFactory), 'The ComponentFactory must be back to its original state.');
    }

    /**
     * @return array{componentTemplateFinder: ComponentTemplateFinderInterface::class, config: array}
     */
    private function extractComponentFactoryState(ComponentFactory $componentFactory): array
    {
        $componentTemplateFinder = \Closure::bind(fn (ComponentFactory $componentFactory) => $componentFactory->componentTemplateFinder, null, $componentFactory)($componentFactory);
        $config = \Closure::bind(fn (ComponentFactory $componentFactory) => $componentFactory->config, null, $componentFactory)($componentFactory);

        return ['componentTemplateFinder' => $componentTemplateFinder, 'config' => $config];
    }
}
