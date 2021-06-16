<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Tests\Fixture\Component\Component1;
use Symfony\UX\LiveComponent\Tests\Fixture\Component\Component2;
use Symfony\UX\LiveComponent\Tests\Fixture\Entity\Entity1;
use Symfony\UX\TwigComponent\ComponentFactory;
use Zenstruck\Browser\Response\HtmlResponse;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use function Zenstruck\Foundry\create;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentSubscriberTest extends KernelTestCase
{
    use Factories, ResetDatabase, HasBrowser;

    /**
     * @test
     */
    public function can_render_component_as_html_or_json(): void
    {
        self::bootKernel();

        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::$container->get(LiveComponentHydrator::class);

        /** @var ComponentFactory $factory */
        $factory = self::$container->get(ComponentFactory::class);

        /** @var Component1 $component */
        $component = $factory->create(Component1::getComponentName(), [
            'prop1' => $entity = create(Entity1::class)->object(),
            'prop2' => $date = new \DateTime('2021-03-05 9:23'),
            'prop3' => 'value3',
            'prop4' => 'value4',
        ]);

        $dehydrated = $hydrator->dehydrate($component);

        $this->browser()
            ->throwExceptions()
            ->get('/components/component1?'.http_build_query($dehydrated))
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Prop1: '.$entity->id)
            ->assertContains('Prop2: 2021-03-05 9:23')
            ->assertContains('Prop3: value3')
            ->assertContains('Prop4: (none)')

            ->get('/components/component1?'.http_build_query($dehydrated), ['headers' => ['Accept' => 'application/vnd.live-component+json']])
            ->assertSuccessful()
            ->assertHeaderEquals('Content-Type', 'application/vnd.live-component+json')
            ->assertJsonMatches('keys(@)', ['html', 'data'])
            ->assertJsonMatches("contains(html, 'Prop1: {$entity->id}')", true)
            ->assertJsonMatches("contains(html, 'Prop2: 2021-03-05 9:23')", true)
            ->assertJsonMatches("contains(html, 'Prop3: value3')", true)
            ->assertJsonMatches("contains(html, 'Prop4: (none)')", true)
            ->assertJsonMatches('keys(data)', ['prop1', 'prop2', 'prop3', '_checksum'])
            ->assertJsonMatches('data.prop1', $entity->id)
            ->assertJsonMatches('data.prop2', $date->format('c'))
            ->assertJsonMatches('data.prop3', 'value3')
        ;
    }

    /**
     * @test
     */
    public function can_execute_component_action(): void
    {
        self::bootKernel();

        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::$container->get(LiveComponentHydrator::class);

        /** @var ComponentFactory $factory */
        $factory = self::$container->get(ComponentFactory::class);

        /** @var Component2 $component */
        $component = $factory->create(Component2::getComponentName());

        $dehydrated = $hydrator->dehydrate($component);
        $token = null;

        $this->browser()
            ->throwExceptions()
            ->get('/components/component2?'.http_build_query($dehydrated))
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Count: 1')
            ->use(function(HtmlResponse $response) use (&$token) {
                // get a valid token to use for actions
                $token = $response->crawler()->filter('div')->first()->attr('data-live-csrf-value');
            })
            ->post('/components/component2/increase?'.http_build_query($dehydrated), [
                'headers' => ['X-CSRF-TOKEN' => $token]
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Count: 2')

            ->get('/components/component2?'.http_build_query($dehydrated), ['headers' => ['Accept' => 'application/vnd.live-component+json']])
            ->assertSuccessful()
            ->assertJsonMatches('data.count', 1)
            ->assertJsonMatches("contains(html, 'Count: 1')", true)
            ->post('/components/component2/increase?'.http_build_query($dehydrated), [
                'headers' => [
                    'Accept' => 'application/vnd.live-component+json',
                    'X-CSRF-TOKEN' => $token,
                ]
            ])
            ->assertSuccessful()
            ->assertJsonMatches('data.count', 2)
            ->assertJsonMatches("contains(html, 'Count: 2')", true)
        ;
    }

    /**
     * @test
     */
    public function cannot_execute_component_action_for_get_request(): void
    {
        $this->browser()
            ->get('/components/component2/increase')
            ->assertStatus(405)
        ;
    }

    /**
     * @test
     */
    public function missing_csrf_token_for_component_action_fails(): void
    {
        $this->browser()
            ->post('/components/component2/increase')
            ->assertStatus(400)
        ;
    }

    /**
     * @test
     */
    public function invalid_csrf_token_for_component_action_fails(): void
    {
        $this->browser()
            ->post('/components/component2/increase', [
                'headers' => ['X-CSRF-TOKEN' => 'invalid']
            ])
            ->assertStatus(400)
        ;
    }

    /**
     * @test
     */
    public function before_re_render_hook_only_executed_during_ajax(): void
    {
        self::bootKernel();

        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::$container->get(LiveComponentHydrator::class);

        /** @var ComponentFactory $factory */
        $factory = self::$container->get(ComponentFactory::class);

        /** @var Component2 $component */
        $component = $factory->create(Component2::getComponentName());

        $dehydrated = $hydrator->dehydrate($component);

        $this->browser()
            ->visit('/render-template/template1')
            ->assertSuccessful()
            ->assertSee('BeforeReRenderCalled: No')
            ->get('/components/component2?'.http_build_query($dehydrated))
            ->assertSuccessful()
            ->assertSee('BeforeReRenderCalled: Yes')
        ;
    }

    /**
     * @test
     */
    public function can_redirect_from_component_action(): void
    {
        self::bootKernel();

        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::$container->get(LiveComponentHydrator::class);

        /** @var ComponentFactory $factory */
        $factory = self::$container->get(ComponentFactory::class);

        /** @var Component2 $component */
        $component = $factory->create(Component2::getComponentName());

        $dehydrated = $hydrator->dehydrate($component);
        $token = null;

        $this->browser()
            ->throwExceptions()
            ->get('/components/component2?'.http_build_query($dehydrated))
            ->assertSuccessful()
            ->use(function(HtmlResponse $response) use (&$token) {
                // get a valid token to use for actions
                $token = $response->crawler()->filter('div')->first()->attr('data-live-csrf-value');
            })
            ->interceptRedirects()
            ->post('/components/component2/redirect?'.http_build_query($dehydrated), [
                'headers' => ['X-CSRF-TOKEN' => $token]
            ])
            ->assertRedirectedTo('/')

            ->post('/components/component2/redirect?'.http_build_query($dehydrated), [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-CSRF-TOKEN' => $token,
                ]
            ])
            ->assertSuccessful()
            ->assertJsonMatches('redirect_url', '/')
        ;
    }
}
