<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\UX\Map\Twig\UXMapComponent;
use Symfony\UX\Map\Twig\UXMapComponentListener;
use Symfony\UX\TwigComponent\Event\PreCreateForRenderEvent;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_map.twig_component_listener', UXMapComponentListener::class)
            ->args([
                service('ux_map.twig_runtime'),
            ])
            ->tag('kernel.event_listener', [
                'event' => PreCreateForRenderEvent::class,
                'method' => 'onPreCreateForRender',
            ])

        ->set('.ux_map.twig_component.map', UXMapComponent::class)
            ->tag('twig.component', ['key' => 'UX:Map'])
    ;
};
