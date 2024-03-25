<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Twig;

use Symfony\UX\Icons\IconRenderer;
use Symfony\UX\TwigComponent\Event\PreCreateForRenderEvent;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class UXIconComponentListener
{
    public function __construct(
        private IconRenderer $iconRenderer,
    ) {
    }

    public function onPreCreateForRender(PreCreateForRenderEvent $event): void
    {
        if ('ux:icon' !== strtolower($event->getName())) {
            return;
        }

        $attributes = $event->getInputProps();
        $name = (string) $attributes['name'];
        unset($attributes['name']);

        $svg = $this->iconRenderer->renderIcon($name, $attributes);
        $event->setRenderedString($svg);
        $event->stopPropagation();
    }
}
