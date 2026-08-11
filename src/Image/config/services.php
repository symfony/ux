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

use Symfony\UX\Image\ImageRenderer;
use Symfony\UX\Image\Twig\ImageExtension;
use Symfony\UX\Image\Twig\ImgRuntime;
use Symfony\UX\Image\Twig\SourceRuntime;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_image.image_renderer', ImageRenderer::class)

        ->set('.ux_image.twig_extension', ImageExtension::class)
            ->tag('twig.extension')

        ->set('.ux_image.twig_runtime', ImgRuntime::class)
            ->args([
                service('.ux_image.image_renderer'),
            ])
            ->tag('twig.runtime')
            ->tag('ux.twig_component.twig_renderer', ['key' => 'ux:img'])

        ->set('.ux_image.twig_source_runtime', SourceRuntime::class)
            ->args([
                service('.ux_image.image_renderer'),
            ])
            ->tag('ux.twig_component.twig_renderer', ['key' => 'ux:source'])
    ;
};
