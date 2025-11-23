<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\UX\Image\Service\PreloadManager;

/**
 * Automatically injects image preload tags into HTML responses.
 *
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
final class PreloadInjectorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PreloadManager $preloadManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -128],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $contentType = $response->headers->get('Content-Type');

        // Only process HTML responses
        if (!$contentType || !str_contains($contentType, 'text/html')) {
            return;
        }

        $preloadTags = $this->preloadManager->getPreloadTags();
        
        if (empty($preloadTags)) {
            return;
        }

        $content = $response->getContent();
        
        // Inject preload tags before </head>
        if (false !== $headPos = stripos($content, '</head>')) {
            $content = substr_replace(
                $content,
                "\n" . $preloadTags . "\n",
                $headPos,
                0
            );
            
            $response->setContent($content);
        }
    }
}

