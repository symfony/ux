<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\UX\Css\Dumper\StylesheetDumper;

/**
 * In debug, brings the stylesheet up to date before the page renders and links to it.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class StylesheetListener
{
    public function __construct(
        private readonly StylesheetDumper $dumper,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        if (str_starts_with($path, '/_wdt') || str_starts_with($path, '/_profiler')) {
            return;
        }

        $this->dumper->update();
    }
}
