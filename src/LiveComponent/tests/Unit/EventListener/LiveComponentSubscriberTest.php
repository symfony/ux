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

final class LiveComponentSubscriberTest extends TestCase
{
    public function testDefaultConstructedSubscriberRejectsRequestWithoutAcceptHeader()
    {
        $subscriber = new LiveComponentSubscriber($this->createMock(ContainerInterface::class));

        $request = new Request();
        $request->attributes->set('_live_component', 'some_component');

        $this->assertFalse(
            $this->callIsLiveComponentRequest($subscriber, $request),
            'Default-constructed subscriber must enforce the Accept-header CSRF check (testMode must default to false).'
        );
    }

    public function testDefaultConstructedSubscriberAcceptsRequestWithProperAcceptHeader()
    {
        $subscriber = new LiveComponentSubscriber($this->createMock(ContainerInterface::class));

        $request = new Request();
        $request->attributes->set('_live_component', 'some_component');
        $request->headers->set('Accept', 'application/vnd.live-component+html');

        $this->assertTrue($this->callIsLiveComponentRequest($subscriber, $request));
    }

    public function testTestModeBypassesAcceptHeaderCheck()
    {
        $subscriber = new LiveComponentSubscriber($this->createMock(ContainerInterface::class), true);

        $request = new Request();
        $request->attributes->set('_live_component', 'some_component');

        $this->assertTrue(
            $this->callIsLiveComponentRequest($subscriber, $request),
            'When testMode is explicitly true the Accept-header check is bypassed.'
        );
    }

    private function callIsLiveComponentRequest(LiveComponentSubscriber $subscriber, Request $request): bool
    {
        $method = new \ReflectionMethod($subscriber, 'isLiveComponentRequest');

        return $method->invoke($subscriber, $request);
    }
}
