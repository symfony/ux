<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Lets ab reuse its connections.
 *
 * ab speaks HTTP/1.0, where a response without a length ends with the connection. Its requests per second then collapse on large pages as soon as several connections are open.
 */
#[AsEventListener]
final class ContentLengthListener
{
    public function __invoke(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $content = $response->getContent();

        if (false !== $content) {
            $response->headers->set('Content-Length', (string) \strlen($content));
        }
    }
}
