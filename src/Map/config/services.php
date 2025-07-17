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

use Symfony\UX\Map\Icon\UxIconRenderer;
use Symfony\UX\Map\Renderer\AbstractRendererFactory;
use Symfony\UX\Map\Renderer\Renderer;
use Symfony\UX\Map\Renderer\Renderers;
use Symfony\UX\Map\Twig\MapExtension;
use Symfony\UX\Map\Twig\MapRuntime;

/*
 * @author Hugo Alliaume <hugo@alliau.me>
 */

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('ux_map.renderers', Renderers::class)
            ->factory([service('ux_map.renderer_factory'), 'fromStrings'])
            ->args([
                abstract_arg('renderers configuration'),
            ])

        ->set('.ux_map.ux_icons.renderer', UxIconRenderer::class)
            ->args([
                service('.ux_icons.icon_renderer')->nullOnInvalid(),
            ])

        ->set('ux_map.renderer_factory.abstract', AbstractRendererFactory::class)
            ->abstract()
            ->args([
                service('stimulus.helper'),
                service('.ux_map.ux_icons.renderer'),
            ])

        ->set('ux_map.renderer_factory', Renderer::class)
            ->args([
                tagged_iterator('ux_map.renderer_factory'),
            ])

        ->set('ux_map.twig_extension', MapExtension::class)
            ->tag('twig.extension')

        ->set('ux_map.twig_runtime', MapRuntime::class)
            ->args([
                service('ux_map.renderers'),
            ])
            ->tag('twig.runtime')
            ->tag('ux.twig_component.twig_renderer', ['key' => 'ux:map'])
    ;
};
