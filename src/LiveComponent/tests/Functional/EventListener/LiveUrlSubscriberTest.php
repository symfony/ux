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
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Address;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Zenstruck\Browser\Test\HasBrowser;

class LiveUrlSubscriberTest extends KernelTestCase
{
    use HasBrowser;
    use LiveComponentTestHelper;

    public function getTestData(): iterable
    {
        yield 'missing_header' => [
            'previousLocation' => null,
            'expectedLocation' => null,
            'props' => [],
            'args' => [],
        ];
        yield 'unknown_previous_location' => [
            'previousLocation' => 'foo/bar',
            'expectedLocation' => null,
            'props' => [],
            'args' => [],
        ];

        yield 'no_prop' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => null,
            'props' => [],
            'args' => [],
        ];

        yield 'no_change' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => '/route_with_prop/foo',
            'props' => [
                'pathProp' => 'foo',
            ],
            'args' => [],
        ];

        yield 'path_prop_changed' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => '/route_with_prop/bar',
            'props' => [
                'pathProp' => 'foo',
            ],
            'args' => [
                'propName' => 'pathProp',
                'propValue' => 'bar',
            ],
        ];

        yield 'path_alias_prop_changed' => [
            'previousLocation' => '/route_with_alias_prop/foo',
            'expectedLocation' => '/route_with_alias_prop/bar',
            'props' => [
                'pathPropWithAlias' => 'foo',
            ],
            'args' => [
                'propName' => 'pathPropWithAlias',
                'propValue' => 'bar',
            ],
        ];

        yield 'query_alias_prop_changed' => [
            'previousLocation' => '/',
            'expectedLocation' => '/?q=search%2Bterm',
            'props' => [],
            'args' => [
                'propName' => 'boundPropWithAlias',
                'propValue' => 'search+term',
            ],
        ];

        yield 'path_and_query_alias_prop_changed' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => '/route_with_prop/baz?q=foo+bar',
            'props' => [
                'pathProp' => 'baz',
            ],
            'args' => [
                'propName' => 'boundPropWithAlias',
                'propValue' => 'foo bar',
            ],
        ];

        $address = new Address();
        $address->address = '123 Main St';
        $address->city = 'Anytown';
        yield 'with an object in query, keys "address" and "city" must be present' => [
            'previousLocation' => '/',
            'expectedLocation' => '/?objectProp%5Baddress%5D=123+Main+St&objectProp%5Bcity%5D=Anytown&q=search',
            'props' => [
                'objectProp' => $address,
            ],
            'args' => [
                'propName' => 'boundPropWithAlias',
                'propValue' => 'search',
            ],
        ];
        $address = new Address();
        $address->address = '123 Main St';
        $address->city = 'Anytown';
        yield 'with an object in query, with "useSerializerForHydration: true", keys "address" and "c" must be present' => [
            'previousLocation' => '/',
            'expectedLocation' => '/?intProp=3&objectPropWithSerializerForHydration%5Baddress%5D=123+Main+St&objectPropWithSerializerForHydration%5Bc%5D=Anytown',
            'props' => [
                'objectPropWithSerializerForHydration' => $address,
            ],
            'args' => [
                'propName' => 'intProp',
                'propValue' => '3',
            ],
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testNewLiveUrlAfterLiveAction(
        ?string $previousLocation,
        ?string $expectedLocation,
        array $props,
        array $args,
    ): void {
        $component = $this->mountComponent('component_with_url_bound_props', $props);
        $dehydrated = $this->dehydrateComponent($component);

        $this->browser()
            ->throwExceptions()
            ->post(
                [] === $args
                    ? '/_components/component_with_url_bound_props'
                    : '/_components/component_with_url_bound_props/updateLiveProp',
                [
                    'body' => [
                        'data' => json_encode([
                            'props' => $dehydrated->getProps(),
                            'args' => $args,
                        ]),
                    ],
                    'headers' => [
                        'X-Live-Url' => $previousLocation,
                    ],
                ]
            )
            ->assertSuccessful()
            ->assertHeaderEquals('X-Live-Url', $expectedLocation);
    }
}
