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
        ];
        yield 'unknown_previous_location' => [
            'previousLocation' => 'foo/bar',
            'expectedLocation' => null,
            'props' => [],
        ];

        yield 'no_prop' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => null,
            'props' => [],
        ];

        yield 'no_change' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => '/route_with_prop/foo',
            'props' => [
                'pathProp' => 'foo',
            ],
        ];

        yield 'prop_changed' => [
            'previousLocation' => '/route_with_prop/foo',
            'expectedLocation' => '/route_with_prop/bar',
            'props' => [
                'pathProp' => 'foo',
            ],
            'updated' => [
                'pathProp' => 'bar',
            ],
        ];

        yield 'alias_prop_changed' => [
            'previousLocation' => '/route_with_alias_prop/foo',
            'expectedLocation' => '/route_with_alias_prop/bar',
            'props' => [
                'pathPropWithAlias' => 'foo',
            ],
            'updated' => [
                'pathPropWithAlias' => 'bar',
            ],
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testNoHeader(
        ?string $previousLocation,
        ?string $expectedLocation,
        array $props,
        array $updated = [],
    ): void {
        $component = $this->mountComponent('component_with_url_bound_props', $props);
        $dehydrated = $this->dehydrateComponent($component);

        $this->browser()
            ->throwExceptions()
            ->post(
                '/_components/component_with_url_bound_props',
                [
                    'body' => [
                        'data' => json_encode([
                            'props' => $dehydrated->getProps(),
                            'updated' => $updated,
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
