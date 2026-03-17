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

use Symfony\Component\HttpKernel\Event\RequestEvent;

final class NativeListener
{
    public const NATIVE_ATTRIBUTE = '_ux_is_native';

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $request->attributes->set(self::NATIVE_ATTRIBUTE, str_contains($request->headers->get('User-Agent', ''), 'Hotwire Native'));
    }
}
