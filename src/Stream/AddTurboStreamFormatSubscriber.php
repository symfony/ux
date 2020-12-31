<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\UX\Turbo\Stream;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\UX\Turbo\TurboBundle;

/**
 * Detects if it's a Turbo Stream request and set the format accordingly.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class AddTurboStreamFormatSubscriber implements EventSubscriberInterface
{
    /**
     * @see https://github.com/hotwired/turbo/issues/24 Explanation of why this hack is necessary
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!($accept = $request->headers->get('Accept')) || !str_starts_with($accept, TurboBundle::STREAM_MEDIA_TYPE)) {
            return;
        }

        $request->setFormat(TurboBundle::STREAM_FORMAT, TurboBundle::STREAM_MEDIA_TYPE);
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
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
        if (TurboBundle::STREAM_FORMAT === $request->getRequestFormat(null)) {
            $request->setRequestFormat(null);
        }
    }

    /**
     * Executed before AddRequestFormatsListener and ResponseListener.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 110],
            KernelEvents::RESPONSE => ['onKernelResponse', 10],
        ];
    }
}
