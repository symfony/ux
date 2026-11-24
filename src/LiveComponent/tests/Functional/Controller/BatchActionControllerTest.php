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
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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

    public function testCanBatchActions(): void
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

    public function testCanBatchActionsWithAlternateRoute(): void
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

    public function testBatchActionsReceiveTheRouteAndItsDefaults(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('route_defaults'));

        $this->browser()
            ->throwExceptions()
            ->post('/route-defaults/route_defaults/_batch', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                        'actions' => [
                            ['name' => 'capture'],
                        ],
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertSee('route: route_defaults_live_route, scope: storefront')
        ;
    }

    public function testRedirect(): void
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

    public function testRedirectWithAcceptHeader(): void
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

    public function testDownloadDoesNotShortCircuitBatch(): void
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

    public function testLastDownloadWinsWithinABatch(): void
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

    public function testException(): void
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

    public function testCannotBatchWithNonLiveAction(): void
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

    public function testAcceptsBatchAtMaxActions(): void
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

    public function testRejectsBatchAboveMaxActions(): void
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

    public function testRejectsEmptyBatch(): void
    {
        $this->browser()
            ->expectException(BadRequestHttpException::class, 'No actions in batch.')
            ->post('/_components/with_actions/_batch', $this->liveRequestBody('with_actions', ['actions' => []]))
        ;
    }

    public function testEmptyBatchDoesNotRenderComponentWithClassLevelIsGrantedForAnonymousUser(): void
    {
        if (!class_exists(IsGranted::class)) {
            $this->markTestSkipped('The security attributes are not available.');
        }

        $body = $this->liveRequestBody('with_admin_only_class', ['actions' => []]);

        $this->browser()
            ->post('/_components/with_admin_only_class', $body)
            ->assertStatus(401)
            ->post('/_components/with_admin_only_class/_batch', $body)
            ->assertStatus(400)
        ;
    }

    public function testEmptyBatchDoesNotRenderComponentWithClassLevelIsGrantedForUserWithoutRole(): void
    {
        if (!class_exists(IsGranted::class)) {
            $this->markTestSkipped('The security attributes are not available.');
        }

        $body = $this->liveRequestBody('with_admin_only_class', ['actions' => []]);

        $this->browser()
            ->actingAs(new InMemoryUser('kevin', 'pass', ['ROLE_USER']))
            ->post('/_components/with_admin_only_class', $body)
            ->assertStatus(403)
            ->post('/_components/with_admin_only_class/_batch', $body)
            ->assertStatus(400)
        ;
    }

    public function testEmptyBatchDoesNotRenderComponentWithIsGrantedOnDefaultAction(): void
    {
        if (!class_exists(IsGranted::class)) {
            $this->markTestSkipped('The security attributes are not available.');
        }

        $body = $this->liveRequestBody('with_admin_only_default_action', ['actions' => []]);

        $this->browser()
            ->post('/_components/with_admin_only_default_action', $body)
            ->assertStatus(401)
            ->post('/_components/with_admin_only_default_action/_batch', $body)
            ->assertStatus(400)
        ;
    }

    public function testEmptyBatchDoesNotRenderComponentWithSecurity(): void
    {
        if (!class_exists(IsGranted::class)) {
            $this->markTestSkipped('The security attributes are not available.');
        }

        $body = $this->liveRequestBody('with_security', ['actions' => []]);

        $this->browser()
            ->post('/_components/with_security', $body)
            ->assertStatus(401)
            ->post('/_components/with_security/_batch', $body)
            ->assertStatus(400)
        ;
    }

    public function testBatchChecksIsGrantedForEachAction(): void
    {
        if (!class_exists(IsGranted::class)) {
            $this->markTestSkipped('The security attributes are not available.');
        }

        $this->browser()
            ->actingAs(new InMemoryUser('kevin', 'pass', ['ROLE_USER']))
            ->post('/_components/with_admin_only_class/_batch', $this->liveRequestBody('with_admin_only_class', [
                'actions' => [['name' => 'noop'], ['name' => 'noop']],
            ]))
            ->assertStatus(403)
            ->post('/_components/with_security/_batch', $this->liveRequestBody('with_security', [
                'actions' => [['name' => 'setUsername'], ['name' => 'setUsername']],
            ]))
            ->assertSuccessful()
            ->assertSee('Username: kevin')
        ;
    }

    private function liveRequestBody(string $componentName, array $data): array
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent($componentName));

        return ['body' => ['data' => json_encode(['props' => $dehydrated->getProps(), ...$data])]];
    }
}
