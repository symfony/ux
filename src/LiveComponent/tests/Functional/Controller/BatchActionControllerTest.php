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
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'))->all();

        $this->browser()
            ->throwExceptions()
            ->get('/_components/with_actions', ['json' => ['data' => $dehydrated]])
            ->assertSuccessful()
            ->assertSee('initial')
            ->use(function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveData = json_decode($rootElement->attr('data-live-data-value'), true);
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $browser->post('/_components/with_actions/add', [
                    'json' => [
                        'data' => $liveData + $liveProps,
                        'args' => ['what' => 'first'],
                    ],
                    'headers' => ['X-CSRF-TOKEN' => $crawler->filter('ul')->first()->attr('data-live-csrf-value')],
                ]);
            })
            ->assertSee('initial')
            ->assertSee('first')
            ->use(function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveData = json_decode($rootElement->attr('data-live-data-value'), true);
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $browser->post('/_components/with_actions/_batch', [
                    'json' => [
                        'data' => $liveData + $liveProps,
                        'actions' => [
                            ['name' => 'add', 'args' => ['what' => 'second']],
                            ['name' => 'add', 'args' => ['what' => 'third']],
                            ['name' => 'add', 'args' => ['what' => 'fourth']],
                        ],
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

    public function testCsrfTokenIsChecked(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'))->all();

        $this->browser()
            ->post('/_components/with_actions/_batch', ['json' => [
                'data' => $dehydrated,
                'actions' => [],
            ]])
            ->assertStatus(400)
        ;
    }

    public function testRedirect(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'))->all();

        $this->browser()
            ->throwExceptions()
            ->get('/_components/with_actions', ['json' => ['data' => $dehydrated]])
            ->assertSuccessful()
            ->interceptRedirects()
            ->use(function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveData = json_decode($rootElement->attr('data-live-data-value'), true);
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $browser->post('/_components/with_actions/_batch', [
                    'json' => [
                        'data' => $liveData + $liveProps,
                        'actions' => [
                            ['name' => 'add', 'args' => ['what' => 'second']],
                            ['name' => 'redirect'],
                            ['name' => 'add', 'args' => ['what' => 'fourth']],
                        ],
                    ],
                    'headers' => ['X-CSRF-TOKEN' => $crawler->filter('ul')->first()->attr('data-live-csrf-value')],
                ]);
            })
            ->assertRedirectedTo('/')
        ;
    }

    public function testException(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'))->all();

        $this->browser()
            ->get('/_components/with_actions', ['json' => ['data' => $dehydrated]])
            ->assertSuccessful()
            ->use(function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveData = json_decode($rootElement->attr('data-live-data-value'), true);
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $browser->post('/_components/with_actions/_batch', [
                    'json' => [
                        'data' => $liveData + $liveProps,
                        'actions' => [
                            ['name' => 'add', 'args' => ['what' => 'second']],
                            ['name' => 'exception'],
                            ['name' => 'add', 'args' => ['what' => 'fourth']],
                        ],
                    ],
                    'headers' => ['X-CSRF-TOKEN' => $crawler->filter('ul')->first()->attr('data-live-csrf-value')],
                ]);
            })
            ->assertStatus(500)
            ->assertContains('Exception message')
        ;
    }

    public function testCannotBatchWithNonLiveAction(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'))->all();

        $this->browser()
            ->get('/_components/with_actions', ['json' => ['data' => $dehydrated]])
            ->assertSuccessful()
            ->use(function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveData = json_decode($rootElement->attr('data-live-data-value'), true);
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $browser->post('/_components/with_actions/_batch', [
                    'json' => [
                        'data' => $liveData + $liveProps,
                        'actions' => [
                            ['name' => 'add', 'args' => ['what' => 'second']],
                            ['name' => 'nonLive'],
                            ['name' => 'add', 'args' => ['what' => 'fourth']],
                        ],
                    ],
                    'headers' => ['X-CSRF-TOKEN' => $crawler->filter('ul')->first()->attr('data-live-csrf-value')],
                ]);
            })
            ->assertStatus(404)
            ->assertContains('The action \"nonLive\" either doesn\'t exist or is not allowed')
        ;
    }
}
