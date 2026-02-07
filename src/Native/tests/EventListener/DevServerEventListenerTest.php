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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\UX\Native\Configuration\Configuration;
use Symfony\UX\Native\Configuration\Rule;
use Symfony\UX\Native\ConfigurationBuilder;
use Symfony\UX\Native\EventListener\DevServerEventListener;

final class DevServerEventListenerTest extends TestCase
{
    public function testServesConfigurationForMatchingPath()
    {
        $builder = new ConfigurationBuilder(sys_get_temp_dir());
        $builder->add('/config/ios_v1.json', new Configuration(
            settings: ['use_local_db' => true],
            rules: [new Rule(['.*'], ['context' => 'default'])],
        ));

        $listener = new DevServerEventListener($builder);
        $request = Request::create('/config/ios_v1.json');
        $event = $this->createRequestEvent($request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        self::assertTrue($event->hasResponse());
        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame(['use_local_db' => true], $data['settings']);
        self::assertTrue($event->isPropagationStopped());
    }

    public function testDoesNothingForNonMatchingPath()
    {
        $builder = new ConfigurationBuilder(sys_get_temp_dir());
        $builder->add('/config/ios_v1.json', new Configuration(settings: ['use_local_db' => true]));

        $listener = new DevServerEventListener($builder);
        $request = Request::create('/config/unknown.json');
        $event = $this->createRequestEvent($request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
        self::assertFalse($event->isPropagationStopped());
    }

    public function testIgnoresSubRequests()
    {
        $builder = new ConfigurationBuilder(sys_get_temp_dir());
        $builder->add('/config/ios_v1.json', new Configuration(settings: ['use_local_db' => true]));

        $listener = new DevServerEventListener($builder);
        $request = Request::create('/config/ios_v1.json');
        $event = $this->createRequestEvent($request, HttpKernelInterface::SUB_REQUEST);

        $listener->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
        self::assertFalse($event->isPropagationStopped());
    }

    private function createRequestEvent(Request $request, int $requestType): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, $requestType);
    }
}
