<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\UX\Native\EventListener\NativeListener;

final class NativeListenerTest extends TestCase
{
    public function testSetsNativeAttributeToTrueWithHotwireNativeUserAgent()
    {
        $listener = new NativeListener();
        $request = Request::create('/', server: ['HTTP_USER_AGENT' => 'Hotwire Native']);
        $event = $this->createRequestEvent($request);

        $listener->onKernelRequest($event);

        self::assertTrue($request->attributes->get(NativeListener::NATIVE_ATTRIBUTE));
    }

    public function testSetsNativeAttributeToTrueWithHotwireNativeInLongerUserAgent()
    {
        $listener = new NativeListener();
        $request = Request::create('/', server: ['HTTP_USER_AGENT' => 'Turbo Native iOS; Hotwire Native Android']);
        $event = $this->createRequestEvent($request);

        $listener->onKernelRequest($event);

        self::assertTrue($request->attributes->get(NativeListener::NATIVE_ATTRIBUTE));
    }

    public function testSetsNativeAttributeToFalseWithStandardUserAgent()
    {
        $listener = new NativeListener();
        $request = Request::create('/', server: ['HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)']);
        $event = $this->createRequestEvent($request);

        $listener->onKernelRequest($event);

        self::assertFalse($request->attributes->get(NativeListener::NATIVE_ATTRIBUTE));
    }

    public function testSetsNativeAttributeToFalseWithEmptyUserAgent()
    {
        $listener = new NativeListener();
        $request = Request::create('/');
        $request->headers->remove('User-Agent');
        $event = $this->createRequestEvent($request);

        $listener->onKernelRequest($event);

        self::assertFalse($request->attributes->get(NativeListener::NATIVE_ATTRIBUTE));
    }

    private function createRequestEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, $requestType);
    }
}
