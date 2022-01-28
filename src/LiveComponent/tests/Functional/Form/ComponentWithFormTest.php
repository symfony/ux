<?php
declare(strict_types=1);

namespace Symfony\UX\LiveComponent\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Tests\ContainerBC;
use Symfony\UX\LiveComponent\Tests\Fixture\Component\Component2;
use Symfony\UX\TwigComponent\ComponentFactory;
use Zenstruck\Browser\Response\HtmlResponse;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ComponentWithFormTest extends KernelTestCase
{
    use ContainerBC;
    use Factories;
    use HasBrowser;
    use ResetDatabase;

    public function testRenderFormComponent(): void
    {
        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::getContainer()->get('ux.live_component.component_hydrator');

        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        /** @var Component2 $component */
        $component = $factory->create('form_component1');

        $dehydrated = $hydrator->dehydrate($component);
        $token = null;

        $this->browser()
            ->throwExceptions()
            ->get('/_components/form_component1?'.http_build_query($dehydrated))
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->use(function (HtmlResponse $response) use (&$token) {
                // get a valid token to use for actions
                $token = $response->crawler()->filter('div')->first()->attr('data-live-csrf-value');
            })
        ;
    }
}
