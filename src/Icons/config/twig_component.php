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

use Symfony\UX\Icons\Twig\UXIconComponent;
use Symfony\UX\Icons\Twig\UXIconComponentListener;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_icons.twig_component_listener', UXIconComponentListener::class)
            ->args([
                service('.ux_icons.icon_renderer'),
            ])
            ->tag('kernel.event_listener', [
                'event' => 'Symfony\UX\TwigComponent\Event\PreCreateForRenderEvent',
                'method' => 'onPreCreateForRender',
            ])

        ->set('.ux_icons.twig_component.icon', UXIconComponent::class)
            ->tag('twig.component', ['key' => 'UX:Icon'])
    ;
};
