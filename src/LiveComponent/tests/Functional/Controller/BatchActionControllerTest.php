<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class BatchActionControllerTest extends KernelTestCase
{
    use HasBrowser;
    use LiveComponentTestHelper;

    public function testCanBatchActions(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'));

        $this->browser()
            ->throwExceptions()
            ->get('/_components/with_actions', ['query' => ['props' => json_encode($dehydrated->getProps())]])
            ->assertSuccessful()
            ->assertSee('initial')
            ->use(function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $browser->post('/_components/with_actions/add', [
                    'body' => [
                        'data' => json_encode([
                            'props' => $liveProps,
                            'args' => ['what' => 'first'],
                        ]),
                    ],
                    'headers' => ['X-CSRF-TOKEN' => $crawler->filter('ul')->first()->attr('data-live-csrf-value')],
                ]);
            })
            ->assertSee('initial')
            ->assertSee('first')
            ->use(function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $browser->post('/_components/with_actions/_batch', [
                    'body' => [
                        'data' => json_encode([
                            'props' => $liveProps,
                            'actions' => [
                                ['name' => 'add', 'args' => ['what' => 'second']],
                                ['name' => 'add', 'args' => ['what' => 'third']],
                                ['name' => 'add', 'args' => ['what' => 'fourth']],
                            ],
                        ]),
                    ],
                    'headers' => ['X-CSRF-TOKEN' => $crawler->filter('ul')->first()->attr('data-live-csrf-value')],
                ]);
            })
            ->assertSee('initial')
            ->assertSee('first')
            ->assertSee('second')
            ->assertSee('third')
            ->assertSee('fourth')
        ;
    }

    public function testCanBatchActionsWithAlternateRoute(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('alternate_route'));

        $this->browser()
            ->throwExceptions()
            ->get('/alt/alternate_route', ['query' => ['props' => json_encode($dehydrated->getProps())]])
            ->assertSuccessful()
            ->assertSee('count: 0')
            ->use(function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('div')->first();
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $browser->post('/alt/alternate_route/_batch', [
                    'body' => [
                        'data' => json_encode([
                            'props' => $liveProps,
                            'actions' => [
                                ['name' => 'increase'],
                                ['name' => 'increase'],
                                ['name' => 'increase'],
                            ],
                        ]),
                    ],
                    'headers' => ['X-CSRF-TOKEN' => $rootElement->attr('data-live-csrf-value')],
                ]);
            })
            ->assertOn('/alt/alternate_route/_batch')
            ->assertSuccessful()
            ->assertSee('count: 3')
        ;
    }

    public function testCsrfTokenIsChecked(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'));

        $this->browser()
            ->post('/_components/with_actions/_batch', ['json' => [
                'props' => $dehydrated->getProps(),
                'actions' => [],
            ]])
            ->assertStatus(400)
        ;
    }

    public function testRedirect(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'));

        $this->browser()
            ->throwExceptions()
            ->get('/_components/with_actions', ['query' => ['props' => json_encode($dehydrated->getProps())]])
            ->assertSuccessful()
            ->interceptRedirects()
            ->use(function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $browser->post('/_components/with_actions/_batch', [
                    'body' => [
                        'data' => json_encode([
                            'props' => $liveProps,
                            'actions' => [
                                ['name' => 'add', 'args' => ['what' => 'second']],
                                ['name' => 'redirect'],
                                ['name' => 'add', 'args' => ['what' => 'fourth']],
                            ],
                        ]),
                    ],
                    'headers' => ['X-CSRF-TOKEN' => $crawler->filter('ul')->first()->attr('data-live-csrf-value')],
                ]);
            })
            ->assertRedirectedTo('/')
        ;
    }

    public function testException(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'));

        $this->browser()
            ->get('/_components/with_actions', ['query' => ['props' => json_encode($dehydrated->getProps())]])
            ->assertSuccessful()
            ->expectException(\RuntimeException::class, 'Exception message')
            ->use(function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $browser->post('/_components/with_actions/_batch', [
                    'body' => [
                        'data' => json_encode([
                            'props' => $liveProps,
                            'actions' => [
                                ['name' => 'add', 'args' => ['what' => 'second']],
                                ['name' => 'exception'],
                                ['name' => 'add', 'args' => ['what' => 'fourth']],
                            ],
                        ]),
                    ],
                    'headers' => ['X-CSRF-TOKEN' => $crawler->filter('ul')->first()->attr('data-live-csrf-value')],
                ]);
            })
        ;
    }

    public function testCannotBatchWithNonLiveAction(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'));

        $this->browser()
            ->get('/_components/with_actions', ['query' => ['props' => json_encode($dehydrated->getProps())]])
            ->assertSuccessful()
            ->expectException(NotFoundHttpException::class, 'The action "nonLive" either doesn\'t exist or is not allowed')
            ->use(function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $browser->post('/_components/with_actions/_batch', [
                    'body' => [
                        'data' => json_encode([
                            'props' => $liveProps,
                            'actions' => [
                                ['name' => 'add', 'args' => ['what' => 'second']],
                                ['name' => 'nonLive'],
                                ['name' => 'add', 'args' => ['what' => 'fourth']],
                            ],
                        ]),
                    ],
                    'headers' => ['X-CSRF-TOKEN' => $crawler->filter('ul')->first()->attr('data-live-csrf-value')],
                ]);
            })
        ;
    }
}
