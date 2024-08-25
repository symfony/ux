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
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\Persistence\persist;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentSubscriberTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use LiveComponentTestHelper;
    use ResetDatabase;

    /**
     * The deterministic id of the "component2" component in render_embedded_with_blocks.html.twig.
     * If that template changes, this will need to be updated.
     */
    public const DETERMINISTIC_ID = 21098427781;
    /**
     * The deterministic id of the "component2" component in render_multiple_embedded_with_blocks.html.twig.
     * If that template changes, this will need to be updated.
     */
    public const DETERMINISTIC_ID_MULTI_2 = 30904230242;

    public function testCanRenderComponentAsHtml(): void
    {
        $component = $this->mountComponent('component1', [
            'prop1' => $entity = persist(Entity1::class),
            'prop2' => $date = new \DateTime('2021-03-05 9:23'),
            'prop3' => 'value3',
            'prop4' => 'value4',
        ]);

        $dehydrated = $this->dehydrateComponent($component);

        $this->browser()
            ->throwExceptions()
            ->post('/_components/component1', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Prop1: '.$entity->id)
            ->assertContains('Prop2: 2021-03-05 9:23')
            ->assertContains('Prop3: value3')
            ->assertContains('Prop4: (none)')
        ;
    }

    public function testCanRenderComponentAsHtmlWithAlternateRoute(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('alternate_route'));

        $this->browser()
            ->throwExceptions()
            ->post('/alt/alternate_route', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertOn('/alt/alternate_route', parts: ['path'])
            ->assertContains('From alternate route. (count: 0)')
        ;
    }

    public function testCanExecuteComponentActionNormalRoute(): void
    {
        $templateName = 'render_embedded_with_blocks.html.twig';
        $obscuredName = '4bd9245af4594aa28cb77583c29e188e';
        $this->addTemplateMap($obscuredName, $templateName);

        $dehydrated = $this->dehydrateComponent(
            $this->mountComponent(
                'component2',
                [
                    'data-host-template' => $obscuredName,
                    'data-embedded-template-index' => self::DETERMINISTIC_ID,
                ]
            )
        );
        $token = null;

        $this->browser()
            ->throwExceptions()
            ->post('/_components/component2', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Count: 1')
            ->use(function (Crawler $crawler) use (&$token) {
                // get a valid token to use for actions
                $token = $crawler->filter('div')->first()->attr('data-live-csrf-value');
            })
            ->post('/_components/component2/increase', [
                'headers' => ['X-CSRF-TOKEN' => $token],
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Count: 2')
            ->assertSee('Embedded content with access to context, like count=2')
        ;
    }

    public function testCanExecuteComponentActionWithAlternateRoute(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('alternate_route'));
        $token = null;

        $this->browser()
            ->throwExceptions()
            ->post('/alt/alternate_route', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertContains('count: 0')
            ->use(function (Crawler $crawler) use (&$token) {
                // get a valid token to use for actions
                $token = $crawler->filter('div')->first()->attr('data-live-csrf-value');
            })
            ->post('/alt/alternate_route/increase', [
                'headers' => ['X-CSRF-TOKEN' => $token],
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertSuccessful()
            ->assertOn('/alt/alternate_route/increase')
            ->assertContains('count: 1')
        ;
    }

    public function testCannotExecuteComponentActionForGetRequest(): void
    {
        $this->browser()
            ->get('/_components/component2/increase')
            ->assertStatus(405)
        ;
    }

    public function testCannotExecuteComponentDefaultActionForGetRequestWhenMethodIsPost(): void
    {
        $this->browser()
            ->get('/_components/with_method_post/__invoke')
            ->assertStatus(405)
        ;
    }

    public function testMissingCsrfTokenForComponentActionFails(): void
    {
        $this->browser()
            ->post('/_components/component2/increase')
            ->assertStatus(400)
        ;

        try {
            $this->browser()
                ->throwExceptions()
                ->post('/_components/component2/increase')
            ;
        } catch (BadRequestHttpException $e) {
            $this->assertSame('Invalid CSRF token.', $e->getMessage());

            return;
        }

        $this->fail('Expected exception not thrown.');
    }

    public function testInvalidCsrfTokenForComponentActionFails(): void
    {
        $this->browser()
            ->post('/_components/component2/increase', [
                'headers' => ['X-CSRF-TOKEN' => 'invalid'],
            ])
            ->assertStatus(400)
        ;

        try {
            $this->browser()
                ->throwExceptions()
                ->post('/_components/component2/increase', [
                    'headers' => ['X-CSRF-TOKEN' => 'invalid'],
                ])
            ;
        } catch (BadRequestHttpException $e) {
            $this->assertSame('Invalid CSRF token.', $e->getMessage());

            return;
        }

        $this->fail('Expected exception not thrown.');
    }

    public function testDisabledCsrfTokenForComponentDoesNotFail(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('disabled_csrf'));

        $this->browser()
            ->throwExceptions()
            ->post('/_components/disabled_csrf', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Count: 1')
            ->post('/_components/disabled_csrf/increase', [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Count: 2')
        ;
    }

    public function testPreReRenderHookOnlyExecutedDuringAjax(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('component2'));

        $this->browser()
            ->visit('/render-template/render_component2')
            ->assertSuccessful()
            ->assertSee('PreReRenderCalled: No')
            ->post('/_components/component2', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertSee('PreReRenderCalled: Yes')
        ;
    }

    public function testItAddsEmbeddedTemplateContextToEmbeddedComponents(): void
    {
        $templateName = 'render_embedded_with_blocks.html.twig';
        $obscuredName = '1918f197faab43278ba06c0a672a2b97';
        $this->addTemplateMap($obscuredName, $templateName);

        $dehydrated = $this->dehydrateComponent(
            $this->mountComponent(
                'component2',
                [
                    'data-host-template' => $obscuredName,
                    'data-embedded-template-index' => self::DETERMINISTIC_ID,
                ]
            )
        );

        $this->browser()
            ->visit('/render-template/render_embedded_with_blocks')
            ->assertSuccessful()
            ->assertSee('PreReRenderCalled: No')
            ->assertSee('Embedded content with access to context, like count=1')
            ->assertSeeElement('.component2')
            ->assertElementAttributeContains('.component2', 'data-live-props-value', '"data-host-template":"'.$obscuredName.'"')
            ->assertElementAttributeContains('.component2', 'data-live-props-value', '"data-embedded-template-index":'.self::DETERMINISTIC_ID)
            ->post('/_components/component2', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertSee('PreReRenderCalled: Yes')
            ->assertSee('Embedded content with access to context, like count=1')
        ;
    }

    public function testItWorksWithNamespacedTemplateNamesForEmbeddedComponents(): void
    {
        $templateName = 'render_embedded_with_blocks.html.twig';
        $obscuredName = 'fb7992f74bbb43c08e47b7cf5c880edb';
        $this->addTemplateMap($obscuredName, $templateName);

        $this->browser()
            ->visit('/render-namespaced-template/render_embedded_with_blocks')
            ->assertSuccessful()
            ->assertElementAttributeContains('.component2', 'data-live-props-value', '"data-host-template":"'.$obscuredName.'"')
        ;
    }

    public function testItUseBlocksFromEmbeddedContextUsingMultipleComponents(): void
    {
        $templateName = 'render_multiple_embedded_with_blocks.html.twig';
        $obscuredName = '5c474b02358c46cca3da7340cc79cc2e';

        $this->addTemplateMap($obscuredName, $templateName);

        $dehydrated = $this->dehydrateComponent(
            $this->mountComponent(
                'component2',
                [
                    'data-host-template' => $obscuredName,
                    'data-embedded-template-index' => self::DETERMINISTIC_ID_MULTI_2,
                ]
            )
        );

        $token = null;

        $this->browser()
            ->visit('/render-template/render_multiple_embedded_with_blocks')
            ->assertSuccessful()
            ->assertSeeIn('#component1', 'Overridden content from component 1')
            ->assertSeeIn('#component2', 'Overridden content from component 2 on same line - count: 1')
            ->assertSeeIn('#component3', 'PreReRenderCalled: No')
            ->use(function (Crawler $crawler) use (&$token) {
                // get a valid token to use for actions
                $token = $crawler->filter('div')->eq(1)->attr('data-live-csrf-value');
            })
            ->post('/_components/component2/increase', [
                'headers' => ['X-CSRF-TOKEN' => $token],
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertSee('Overridden content from component 2 on same line - count: 2')
        ;
    }

    public function testItUseBlocksFromEmbeddedContextUsingMultipleComponentsWithNamespacedTemplate(): void
    {
        $templateName = 'render_multiple_embedded_with_blocks.html.twig';
        $obscuredName = '5c474b02358c46cca3da7340cc79cc2e';

        $this->addTemplateMap($obscuredName, $templateName);

        $dehydrated = $this->dehydrateComponent(
            $this->mountComponent(
                'component2',
                [
                    'data-host-template' => $obscuredName,
                    'data-embedded-template-index' => self::DETERMINISTIC_ID_MULTI_2,
                ]
            )
        );

        $token = null;

        $this->browser()
            ->visit('/render-namespaced-template/render_multiple_embedded_with_blocks')
            ->assertSuccessful()
            ->assertSeeIn('#component1', 'Overridden content from component 1')
            ->assertSeeIn('#component2', 'Overridden content from component 2 on same line - count: 1')
            ->assertSeeIn('#component3', 'PreReRenderCalled: No')
            ->use(function (Crawler $crawler) use (&$token) {
                // get a valid token to use for actions
                $token = $crawler->filter('div')->eq(1)->attr('data-live-csrf-value');
            })
            ->post('/_components/component2/increase', [
                'headers' => ['X-CSRF-TOKEN' => $token],
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertSee('Overridden content from component 2 on same line - count: 2')
        ;
    }

    public function testCanRedirectFromComponentAction(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('component2'));
        $token = null;

        $this->browser()
            ->throwExceptions()
            ->post('/_components/component2', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->use(function (Crawler $crawler) use (&$token) {
                // get a valid token to use for actions
                $token = $crawler->filter('div')->first()->attr('data-live-csrf-value');
            })
            ->interceptRedirects()
            // with no custom header, it redirects like a normal browser
            ->post('/_components/component2/redirect', [
                'headers' => ['X-CSRF-TOKEN' => $token],
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertRedirectedTo('/')

            // with custom header, a special 204 is returned
            ->post('/_components/component2/redirect', [
                'headers' => [
                    'Accept' => 'application/vnd.live-component+html',
                    'X-CSRF-TOKEN' => $token,
                ],
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertStatus(204)
            ->assertHeaderEquals('Location', '/')
            ->assertHeaderContains('X-Live-Redirect', '1')
            ->assertHeaderEquals('X-Custom-Header', '1')
        ;
    }

    public function testInjectsLiveArgs(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('component6'));
        $token = null;

        $arguments = ['arg1' => 'hello', 'arg2' => 666, 'custom' => '33.3'];
        $this->browser()
            ->throwExceptions()
            ->post('/_components/component6', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Arg1: not provided')
            ->assertContains('Arg2: not provided')
            ->assertContains('Arg3: not provided')
            ->use(function (Crawler $crawler) use (&$token) {
                // get a valid token to use for actions
                $token = $crawler->filter('div')->first()->attr('data-live-csrf-value');
            })
            ->post('/_components/component6/inject', [
                'headers' => ['X-CSRF-TOKEN' => $token],
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                        'args' => $arguments,
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Arg1: hello')
            ->assertContains('Arg2: 666')
            ->assertContains('Arg3: 33.3')
        ;
    }

    public function testWithNullableEntity(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_nullable_entity'));

        $this->browser()
            ->throwExceptions()
            ->post('/_components/with_nullable_entity', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertContains('Prop1: default')
        ;
    }

    public function testCanHaveControllerAttributes(): void
    {
        if (!class_exists(IsGranted::class)) {
            $this->markTestSkipped('The security attributes are not available.');
        }

        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_security'));

        $this->browser()
            ->post('/_components/with_security?props='.urlencode(json_encode($dehydrated->getProps())))
            ->assertStatus(401)
            ->actingAs(new InMemoryUser('kevin', 'pass', ['ROLE_USER']))
            ->assertAuthenticated('kevin')
            ->post('/_components/with_security?props='.urlencode(json_encode($dehydrated->getProps())))
            ->assertSuccessful()
        ;
    }

    public function testCanInjectSecurityUserIntoAction(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_security'));
        $token = null;

        $this->browser()
            ->actingAs(new InMemoryUser('kevin', 'pass', ['ROLE_USER']))
            ->post('/_components/with_security', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertNotSee('username: kevin')
            ->use(function (Crawler $crawler) use (&$token) {
                // get a valid token to use for actions
                $token = $crawler->filter('div')->first()->attr('data-live-csrf-value');
            })
            ->throwExceptions()
            ->post('/_components/with_security/setUsername', [
                'headers' => ['X-CSRF-TOKEN' => $token],
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                        'args' => [],
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertSee('username: kevin')
        ;
    }
}
