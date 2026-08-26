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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Controller\BatchActionController;
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

    public function testCanBatchActions()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'));

        $this->browser()
            ->throwExceptions()
            ->post('/_components/with_actions', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertSee('initial')
            ->use(static function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $browser->post('/_components/with_actions/add', [
                    'body' => [
                        'data' => json_encode([
                            'props' => $liveProps,
                            'args' => ['what' => 'first'],
                        ]),
                    ],
                ]);
            })
            ->assertSee('initial')
            ->assertSee('first')
            ->use(static function (Crawler $crawler, KernelBrowser $browser) {
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
                ]);
            })
            ->assertSee('initial')
            ->assertSee('first')
            ->assertSee('second')
            ->assertSee('third')
            ->assertSee('fourth')
        ;
    }

    public function testCanBatchActionsWithAlternateRoute()
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
            ->assertSee('count: 0')
            ->use(static function (Crawler $crawler, KernelBrowser $browser) {
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
                ]);
            })
            ->assertOn('/alt/alternate_route/_batch')
            ->assertSuccessful()
            ->assertSee('count: 3')
        ;
    }

    public function testRedirect()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'));

        $this->browser()
            ->throwExceptions()
            ->post('/_components/with_actions', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->interceptRedirects()
            ->use(static function (Crawler $crawler, KernelBrowser $browser) {
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
                ]);
            })
            ->assertRedirectedTo('/')
        ;
    }

    public function testRedirectWithAcceptHeader()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'));

        $this->browser()
            ->throwExceptions()
            ->post('/_components/with_actions', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->interceptRedirects()
            ->use(static function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $browser->post('/_components/with_actions/_batch', [
                    'body' => [
                        'data' => json_encode([
                            'props' => $liveProps,
                            'actions' => [
                                ['name' => 'redirect'],
                                ['name' => 'exception'],
                            ],
                        ]),
                    ],
                    'headers' => [
                        'Accept' => ['application/vnd.live-component+html'],
                        'X-Requested-With' => ['XMLHttpRequest'],
                    ],
                ]);
            })
            ->assertStatus(204)
            ->assertHeaderContains('X-Live-Redirect', '1')
        ;
    }

    public function testDownloadDoesNotShortCircuitBatch()
    {
        // unlike a redirect, a download no longer ends the batch: it rides along with the
        // final render, so the actions queued after it still run
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $this->browser()
            ->throwExceptions()
            ->post('/_components/download_file/_batch', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                        'actions' => [
                            ['name' => 'download'],
                            ['name' => 'noDownload'],
                        ],
                    ]),
                ],
            ])
            ->assertStatus(200)
            ->assertSeeIn('#count', '2')
            ->use(static function (KernelBrowser $browser) {
                $headers = $browser->client()->getResponse()->headers;
                $body = $browser->client()->getInternalResponse()->getContent();

                self::assertSame('foo.json', rawurldecode($headers->get('X-Live-Download-Filename')));
                self::assertSame(
                    ['foo' => 'bar'],
                    json_decode(substr($body, (int) $headers->get('X-Live-Html-Length')), true)
                );
            })
        ;
    }

    public function testLastDownloadWinsWithinABatch()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $this->browser()
            ->throwExceptions()
            ->post('/_components/download_file/_batch', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                        'actions' => [
                            ['name' => 'download'],
                            ['name' => 'downloadEmpty'],
                        ],
                    ]),
                ],
            ])
            ->assertStatus(200)
            ->use(static function (KernelBrowser $browser) {
                $headers = $browser->client()->getResponse()->headers;

                self::assertSame('empty.txt', rawurldecode($headers->get('X-Live-Download-Filename')));
            })
        ;
    }

    public function testException()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'));

        $this->browser()
            ->post('/_components/with_actions', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->expectException(\RuntimeException::class, 'Exception message')
            ->use(static function (Crawler $crawler, KernelBrowser $browser) {
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
                ]);
            })
        ;
    }

    public function testCannotBatchWithNonLiveAction()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'));

        $this->browser()
            ->post('/_components/with_actions', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->expectException(NotFoundHttpException::class, 'The action "nonLive" either doesn\'t exist or is not allowed')
            ->use(static function (Crawler $crawler, KernelBrowser $browser) {
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
                ]);
            })
        ;
    }

    public function testAcceptsBatchAtMaxActions()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'));

        $this->browser()
            ->throwExceptions()
            ->post('/_components/with_actions', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->use(static function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $actions = [];
                for ($i = 0; $i < BatchActionController::MAX_ACTIONS_PER_BATCH; ++$i) {
                    $actions[] = ['name' => 'add', 'args' => ['what' => "item-$i"]];
                }

                $browser->post('/_components/with_actions/_batch', [
                    'body' => [
                        'data' => json_encode([
                            'props' => $liveProps,
                            'actions' => $actions,
                        ]),
                    ],
                ]);
            })
            ->assertSuccessful()
            ->assertSee('item-0')
            ->assertSee('item-49')
        ;
    }

    public function testRejectsBatchAboveMaxActions()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_actions'));

        $this->browser()
            ->post('/_components/with_actions', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->expectException(BadRequestHttpException::class, 'Too many actions in batch.')
            ->use(static function (Crawler $crawler, KernelBrowser $browser) {
                $rootElement = $crawler->filter('ul')->first();
                $liveProps = json_decode($rootElement->attr('data-live-props-value'), true);

                $actions = array_fill(0, BatchActionController::MAX_ACTIONS_PER_BATCH + 1, ['name' => 'add', 'args' => ['what' => 'x']]);

                $browser->post('/_components/with_actions/_batch', [
                    'body' => [
                        'data' => json_encode([
                            'props' => $liveProps,
                            'actions' => $actions,
                        ]),
                    ],
                ]);
            })
        ;
    }
}
