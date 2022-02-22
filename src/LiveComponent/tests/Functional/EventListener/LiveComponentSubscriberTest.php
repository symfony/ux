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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
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
            ->get('/_components/component1?data='.urlencode(json_encode($dehydrated)))
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Prop1: '.$entity->id)
            ->assertContains('Prop2: 2021-03-05 9:23')
            ->assertContains('Prop3: value3')
            ->assertContains('Prop4: (none)')
        ;
    }

    public function testCanExecuteComponentAction(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('component2'));
        $token = null;

        $this->browser()
            ->throwExceptions()
            ->get('/_components/component2?data='.urlencode(json_encode($dehydrated)))
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Count: 1')
            ->use(function (HtmlResponse $response) use (&$token) {
                // get a valid token to use for actions
                $token = $response->crawler()->filter('div')->first()->attr('data-live-csrf-value');
            })
            ->post('/_components/component2/increase', [
                'headers' => ['X-CSRF-TOKEN' => $token],
                'body' => json_encode($dehydrated),
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Count: 2')
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

    public function testBeforeReRenderHookOnlyExecutedDuringAjax(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('component2'));

        $this->browser()
            ->visit('/render-template/template1')
            ->assertSuccessful()
            ->assertSee('BeforeReRenderCalled: No')
            ->get('/_components/component2?data='.urlencode(json_encode($dehydrated)))
            ->assertSuccessful()
            ->assertSee('BeforeReRenderCalled: Yes')
        ;
    }

    public function testCanRedirectFromComponentAction(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('component2'));
        $token = null;

        $this->browser()
            ->throwExceptions()
            ->get('/_components/component2?data='.urlencode(json_encode($dehydrated)))
            ->assertSuccessful()
            ->use(function (HtmlResponse $response) use (&$token) {
                // get a valid token to use for actions
                $token = $response->crawler()->filter('div')->first()->attr('data-live-csrf-value');
            })
            ->interceptRedirects()
            // with no custom header, it redirects like a normal browser
            ->post('/_components/component2/redirect', [
                'headers' => ['X-CSRF-TOKEN' => $token],
                'body' => json_encode($dehydrated),
            ])
            ->assertRedirectedTo('/')

            // with custom header, a special 204 is returned
            ->post('/_components/component2/redirect', [
                'headers' => [
                    'Accept' => 'application/vnd.live-component+html',
                    'X-CSRF-TOKEN' => $token,
                ],
                'body' => json_encode($dehydrated),
            ])
            ->assertStatus(204)
            ->assertHeaderEquals('Location', '/')
        ;
    }

    public function testInjectsLiveArgs(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('component6'));
        $token = null;

        $argsQueryParams = http_build_query(['args' => http_build_query(['arg1' => 'hello', 'arg2' => 666, 'custom' => '33.3'])]);
        $this->browser()
            ->throwExceptions()
            ->get('/_components/component6?data='.urlencode(json_encode($dehydrated)).'&'.$argsQueryParams)
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Arg1: not provided')
            ->assertContains('Arg2: not provided')
            ->assertContains('Arg3: not provided')
            ->use(function (HtmlResponse $response) use (&$token) {
                // get a valid token to use for actions
                $token = $response->crawler()->filter('div')->first()->attr('data-live-csrf-value');
            })
            ->post('/_components/component6/inject?'.$argsQueryParams, [
                'headers' => ['X-CSRF-TOKEN' => $token],
                'body' => json_encode($dehydrated),
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Arg1: hello')
            ->assertContains('Arg2: 666')
            ->assertContains('Arg3: 33.3')
        ;
    }
}
