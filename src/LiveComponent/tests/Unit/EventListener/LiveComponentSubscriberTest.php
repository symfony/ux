<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\EventListener\LiveComponentSubscriber;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
class LiveComponentSubscriberTest extends TestCase
{
    /**
     * @dataProvider provideProductionGateScenarios
     */
    public function testProductionGateRequiresNonSafelistedHeader(array $headers, bool $expected): void
    {
        $subscriber = new LiveComponentSubscriber($this->createMock(ContainerInterface::class), testMode: false);

        $request = new Request();
        $request->attributes->set('_live_component', 'some-component');
        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }

        $isLiveRequest = (new \ReflectionMethod($subscriber, 'isLiveComponentRequest'))->invoke($subscriber, $request);

        $this->assertSame($expected, $isLiveRequest);
    }

    public static function provideProductionGateScenarios(): iterable
    {
        yield 'accept header only is not enough (CSRF bypass closed)' => [
            ['Accept' => 'application/vnd.live-component+html'],
            false,
        ];

        yield 'X-Requested-With only without correct Accept is rejected' => [
            ['X-Requested-With' => 'XMLHttpRequest'],
            false,
        ];

        yield 'both headers present accepts the request' => [
            [
                'Accept' => 'application/vnd.live-component+html',
                'X-Requested-With' => 'XMLHttpRequest',
            ],
            true,
        ];

        yield 'wrong X-Requested-With value is rejected' => [
            [
                'Accept' => 'application/vnd.live-component+html',
                'X-Requested-With' => 'something-else',
            ],
            false,
        ];
    }

    public function testRequestWithoutLiveComponentAttributeIsRejected(): void
    {
        $subscriber = new LiveComponentSubscriber($this->createMock(ContainerInterface::class), testMode: false);

        $request = new Request();
        $request->headers->set('Accept', 'application/vnd.live-component+html');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $isLiveRequest = (new \ReflectionMethod($subscriber, 'isLiveComponentRequest'))->invoke($subscriber, $request);

        $this->assertFalse($isLiveRequest);
    }
}
