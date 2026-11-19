<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventListener;

use App\EventListener\ContentLengthListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ContentLengthListenerTest extends TestCase
{
    public function testItSetsTheLengthOfTheContentInBytes(): void
    {
        $response = new Response('héllo');
        $event = $this->event($response);

        new ContentLengthListener()($event);

        self::assertSame('6', $response->headers->get('Content-Length'));
    }

    public function testItLeavesAResponseWithoutContentAlone(): void
    {
        $response = new StreamedResponse(static function (): void {});
        $event = $this->event($response);

        new ContentLengthListener()($event);

        self::assertFalse($response->headers->has('Content-Length'));
    }

    private function event(Response $response): ResponseEvent
    {
        return new ResponseEvent($this->createStub(HttpKernelInterface::class), new Request(), HttpKernelInterface::MAIN_REQUEST, $response);
    }
}
