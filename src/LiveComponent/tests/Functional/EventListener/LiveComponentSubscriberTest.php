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
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\create;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentSubscriberTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use LiveComponentTestHelper;
    use ResetDatabase;

    public function testCanRenderComponentAsHtml(): void
    {
        $component = $this->mountComponent('component1', [
            'prop1' => $entity = create(Entity1::class)->object(),
            'prop2' => $date = new \DateTime('2021-03-05 9:23'),
            'prop3' => 'value3',
            'prop4' => 'value4',
        ]);

        $dehydrated = $this->dehydrateComponent($component);

        $this->browser()
            ->throwExceptions()
            ->get('/_components/component1?props='.urlencode(json_encode($dehydrated->getProps())))
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
            ->get('/alt/alternate_route?props='.urlencode(json_encode($dehydrated->getProps())))
            ->assertSuccessful()
            ->assertOn('/alt/alternate_route', parts: ['path'])
            ->assertContains('From alternate route. (count: 0)')
        ;
    }

    public function testCanExecuteComponentActionNormalRoute(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('component2'));
        $token = null;

        $this->browser()
            ->throwExceptions()
            ->get('/_components/component2?props='.urlencode(json_encode($dehydrated->getProps())))
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Count: 1')
            ->use(function (Crawler $crawler) use (&$token) {
                // get a valid token to use for actions
                $token = $crawler->filter('div')->first()->attr('data-live-csrf-value');
            })
            ->post('/_components/component2/increase', [
                'headers' => ['X-CSRF-TOKEN' => $token],
                'body' => json_encode(['props' => $dehydrated->getProps()]),
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Count: 2')
        ;
    }

    public function testCanExecuteComponentActionWithAlternateRoute(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('alternate_route'));
        $token = null;

        $this->browser()
            ->throwExceptions()
            ->get('/alt/alternate_route?props='.urlencode(json_encode($dehydrated->getProps())))
            ->assertSuccessful()
            ->assertContains('count: 0')
            ->use(function (Crawler $crawler) use (&$token) {
                // get a valid token to use for actions
                $token = $crawler->filter('div')->first()->attr('data-live-csrf-value');
            })
            ->post('/alt/alternate_route/increase', [
                'headers' => ['X-CSRF-TOKEN' => $token],
                'body' => json_encode(['props' => $dehydrated->getProps()]),
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
            ->get('/_components/disabled_csrf?props='.urlencode(json_encode($dehydrated->getProps())))
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Count: 1')
            ->post('/_components/disabled_csrf/increase', [
                'body' => json_encode(['props' => $dehydrated->getProps()]),
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
            ->get('/_components/component2?props='.urlencode(json_encode($dehydrated->getProps())))
            ->assertSuccessful()
            ->assertSee('PreReRenderCalled: Yes')
        ;
    }

    public function testCanRedirectFromComponentAction(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('component2'));
        $token = null;

        $this->browser()
            ->throwExceptions()
            ->get('/_components/component2?props='.urlencode(json_encode($dehydrated->getProps())))
            ->assertSuccessful()
            ->use(function (Crawler $crawler) use (&$token) {
                // get a valid token to use for actions
                $token = $crawler->filter('div')->first()->attr('data-live-csrf-value');
            })
            ->interceptRedirects()
            // with no custom header, it redirects like a normal browser
            ->post('/_components/component2/redirect', [
                'headers' => ['X-CSRF-TOKEN' => $token],
                'body' => json_encode(['props' => $dehydrated->getProps()]),
            ])
            ->assertRedirectedTo('/')

            // with custom header, a special 204 is returned
            ->post('/_components/component2/redirect', [
                'headers' => [
                    'Accept' => 'application/vnd.live-component+html',
                    'X-CSRF-TOKEN' => $token,
                ],
                'body' => json_encode(['props' => $dehydrated->getProps()]),
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
            ->get('/_components/component6?props='.urlencode(json_encode($dehydrated->getProps())))
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
                'body' => json_encode([
                    'props' => $dehydrated->getProps(),
                    'args' => $arguments,
                ]),
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
            ->get('/_components/with_nullable_entity?props='.urlencode(json_encode($dehydrated->getProps())))
            ->assertSuccessful()
            ->assertContains('Prop1: default')
        ;
    }
}
