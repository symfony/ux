<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Stream;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Detects if it's a Turbo Stream request and sets the format accordingly.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @experimental
 */
final class AddTurboStreamFormatSubscriber implements EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!($accept = $request->headers->get('Accept')) || 0 !== strpos($accept, TurboStreamResponse::STREAM_MEDIA_TYPE)) {
            return;
        }

        $request->setFormat(TurboStreamResponse::STREAM_FORMAT, TurboStreamResponse::STREAM_MEDIA_TYPE);
        $request->setRequestFormat(TurboStreamResponse::STREAM_FORMAT);
    }

    /**
     * Prevents issues with Turbo Frames when sending a response that isn't in the Turbo Stream format.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if ($response instanceof TurboStreamResponse) {
            return;
        }

        $request = $event->getRequest();
        if (TurboStreamResponse::STREAM_FORMAT === $request->getRequestFormat(null)) {
            $request->setRequestFormat(null);
        }
    }

    /**
     * Executed before AddRequestFormatsListener and ResponseListener.
     *
     * @return array<string, array>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 110],
            KernelEvents::RESPONSE => ['onKernelResponse', 10],
        ];
    }
}
