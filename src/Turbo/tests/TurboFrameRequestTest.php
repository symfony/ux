<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\Turbo\TurboFrame;

/**
 * @author Sébastien JEAN <sebastien.jean76@gmail.com>
 */
class TurboFrameRequestTest extends TestCase
{
    public function testIsRequestReturnsFalseWithoutHeader()
    {
        $turboFrame = $this->createTurboFrame(Request::create('/'));

        $this->assertFalse($turboFrame->isFrameRequest());
    }

    public function testIsRequestReturnsTrueWithHeader()
    {
        $request = Request::create('/');
        $request->headers->set('Turbo-Frame', 'my_frame');

        $turboFrame = $this->createTurboFrame($request);

        $this->assertTrue($turboFrame->isFrameRequest());
    }

    public function testGetRequestIdReturnsNullWithoutHeader()
    {
        $turboFrame = $this->createTurboFrame(Request::create('/'));

        $this->assertNull($turboFrame->getRequestId());
    }

    public function testGetRequestIdReturnsFrameId()
    {
        $request = Request::create('/');
        $request->headers->set('Turbo-Frame', 'my_frame');

        $turboFrame = $this->createTurboFrame($request);

        $this->assertSame('my_frame', $turboFrame->getRequestId());
    }

    public function testGetRequestIdReturnsNullWithNoCurrentRequest()
    {
        $requestStack = new RequestStack();
        $turboFrame = new TurboFrame($requestStack);

        $this->assertNull($turboFrame->getRequestId());
        $this->assertFalse($turboFrame->isFrameRequest());
    }

    private function createTurboFrame(Request $request): TurboFrame
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new TurboFrame($requestStack);
    }
}
