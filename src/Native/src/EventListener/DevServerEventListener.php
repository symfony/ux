<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\UX\Native\ConfigurationBuilder;

final class DevServerEventListener
{
    public function __construct(
        private ConfigurationBuilder $configurationBuilder,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();
        if (!$this->configurationBuilder->has($pathInfo)) {
            return;
        }

        $configuration = $this->configurationBuilder->get($pathInfo);
        $event->setResponse(new JsonResponse($configuration->toArray()));
        $event->stopPropagation();
    }
}
