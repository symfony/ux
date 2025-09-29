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
        yield 'Missing header' => [
            'previousLocation' => null,
            'expectedLocation' => null,
            'initialComponentData' => [],
            'args' => [],
        ];

        yield 'Unknown previous location' => [
            'previousLocation' => 'foo/bar',
            'expectedLocation' => 'foo/bar',
            'initialComponentData' => [],
            'args' => [],
        ];

        yield 'No props change' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => '/route_with_prop/foo',
            'initialComponentData' => [
                'pathProp' => 'foo',
            ],
            'args' => [],
        ];

        yield 'Changes in prop' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => '/route_with_prop/bar',
            'initialComponentData' => [
                'pathProp' => 'foo',
            ],
            'args' => [
                'propName' => 'pathProp',
                'propValue' => 'bar',
            ],
        ];

        yield 'Change in query' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => '/route_with_prop/foo?stringProp=hello',
            'initialComponentData' => [
                'pathProp' => 'foo',
            ],
            'args' => [
                'propName' => 'stringProp',
                'propValue' => 'hello',
            ],
        ];

        yield 'Change in query (with an existing query that is not a LiveProp)' => [
            'previousLocation' => '/route_with_prop/foo?not-a-prop=value',
            'expectedLocation' => '/route_with_prop/foo?not-a-prop=value&stringProp=hello',
            'initialComponentData' => [
                'pathProp' => 'foo',
            ],
            'args' => [
                'propName' => 'stringProp',
                'propValue' => 'hello',
            ],
        ];
        yield 'Change in query (with two existing query, one LiveProp, one non-LiveProp)' => [
            'previousLocation' => '/route_with_prop/foo?not-a-prop=value&stringProp=hello',
            'expectedLocation' => '/route_with_prop/foo?not-a-prop=value&stringProp=bye',
            'initialComponentData' => [
                'pathProp' => 'foo',
                'stringProp' => 'hello',
            ],
            'args' => [
                'propName' => 'stringProp',
                'propValue' => 'bye',
            ],
        ];

        yield 'Changes in prop (alias)' => [
            'previousLocation' => '/route_with_alias_prop/foo',
            'expectedLocation' => '/route_with_alias_prop/bar',
            'initialComponentData' => [
                'pathPropWithAlias' => 'foo',
            ],
            'args' => [
                'propName' => 'pathPropWithAlias',
                'propValue' => 'bar',
            ],
        ];

        yield 'Changes in prop, with two props' => [
            'previousLocation' => '/route_with_two_props/foo/alias',
            'expectedLocation' => '/route_with_two_props/bar/alias',
            'initialComponentData' => [
                'pathProp' => 'foo',
                'pathPropWithAlias' => 'alias',
            ],
            'args' => [
                'propName' => 'pathProp',
                'propValue' => 'bar',
            ],
        ];

        yield 'Changes in prop, with two path params but only one prop' => [
            'previousLocation' => '/route_with_two_path_params_but_one_prop/foo/30',
            'expectedLocation' => '/route_with_two_path_params_but_one_prop/bar/30',
            'initialComponentData' => [
                'pathProp' => 'foo',
            ],
            'args' => [
                'propName' => 'pathProp',
                'propValue' => 'bar',
            ],
        ];

        yield 'Changes in query (alias)' => [
            'previousLocation' => '/',
            'expectedLocation' => '/?q=search+term',
            'initialComponentData' => [],
            'args' => [
                'propName' => 'boundPropWithAlias',
                'propValue' => 'search term',
            ],
        ];

        yield 'Change in query (array)' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => '/route_with_prop/foo?arrayProp%5B0%5D=hello&arrayProp%5B1%5D=world',
            'initialComponentData' => [
                'pathProp' => 'foo',
            ],
            'args' => [
                'propName' => 'arrayProp',
                'propValue' => ['hello', 'world'],
            ],
        ];

        yield 'Change in query (array & alias)' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => '/route_with_prop/foo?arr_alias%5B0%5D=hello&arr_alias%5B1%5D=world',
            'initialComponentData' => [
                'pathProp' => 'foo',
            ],
            'args' => [
                'propName' => 'arrayPropAlias',
                'propValue' => ['hello', 'world'],
            ],
        ];

        yield 'Change in query (array & field name)' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => '/route_with_prop/foo?arr_field_name%5B0%5D=hello&arr_field_name%5B1%5D=world',
            'initialComponentData' => [
                'pathProp' => 'foo',
            ],
            'args' => [
                'propName' => 'arrayPropFieldName',
                'propValue' => ['hello', 'world'],
            ],
        ];

        yield 'Changes in props and query' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => '/route_with_prop/baz?q=foo+bar',
            'initialComponentData' => [
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
            'initialComponentData' => [
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
            'initialComponentData' => [
                'objectPropWithSerializerForHydration' => $address,
            ],
            'args' => [
                'propName' => 'intProp',
                'propValue' => '3',
            ],
        ];

        yield 'query with alias ("p") and modifier (prefix by "alias_")' => [
            'previousLocation' => '/',
            'expectedLocation' => '/?alias_p=test',
            'initialComponentData' => [
                'propertyWithModifierAndAlias' => 'test',
            ],
            'args' => [],
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testNewLiveUrlAfterLiveAction(
        ?string $previousLocation,
        ?string $expectedLocation,
        array $initalComponentData,
        array $args,
    ): void {
        $component = $this->mountComponent('component_with_url_bound_props', $initalComponentData);
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
