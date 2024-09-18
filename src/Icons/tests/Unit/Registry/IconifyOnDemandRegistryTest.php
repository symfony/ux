<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\Registry;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\UX\Icons\Icon;
use Symfony\UX\Icons\Iconify;
use Symfony\UX\Icons\Registry\IconifyOnDemandRegistry;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class IconifyOnDemandRegistryTest extends TestCase
{
    public function testWithIconSetAlias(): void
    {
        $client = new MockHttpClient([
            new JsonMockResponse(['lucide' => []]),
            new JsonMockResponse([
                'icons' => [
                    'circle' => [
                        'body' => '<path aria-label="lucide:circle"/>',
                        'height' => 24,
                    ],
                ],
            ]),
        ]);

        $registry = new IconifyOnDemandRegistry(
            new Iconify(new NullAdapter(), 'https://example.com', $client),
            ['bi' => 'lucide'],
        );

        $icon = $registry->get('bi:circle');
        $this->assertInstanceOf(Icon::class, $icon);
        $this->assertSame('<path aria-label="lucide:circle"/>', $icon->getInnerSvg());
    }
}
