<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Twig;

use Symfony\UX\TwigComponent\Event\PreCreateForRenderEvent;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class UXMapComponentListener
{
    public function __construct(
        private MapRuntime $mapRuntime,
    ) {
    }

    public function onPreCreateForRender(PreCreateForRenderEvent $event): void
    {
        if ('ux:map' !== strtolower($event->getName())) {
            return;
        }

        $attributes = $event->getInputProps();
        $map = array_intersect_key($attributes, ['markers' => 0, 'polygons' => 0, 'center' => 1, 'zoom' => 2]);
        $attributes = array_diff_key($attributes, $map);

        $html = $this->mapRuntime->renderMap(...$map, attributes: $attributes);
        $event->setRenderedString($html);
        $event->stopPropagation();
    }
}
