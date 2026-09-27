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

use Symfony\UX\Image\ImageUrlGenerator;
use Symfony\UX\Image\Provider\ProviderInterface;
use Symfony\UX\Image\Provider\ProviderResolver;
use Symfony\UX\Image\Renderer\ImageRenderer;
use Symfony\UX\Image\Renderer\ImageRendererInterface;
use Symfony\UX\Image\Renderer\LayoutResolver;
use Symfony\UX\Image\Twig\ImageExtension;
use Symfony\UX\Image\Twig\ImageRuntime;

/*
 * @author Hugo Alliaume <hugo@alliau.me>
 */

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('ux_image.provider_resolver', ProviderResolver::class)
            ->args([
                tagged_iterator('ux_image.provider_factory'),
            ])

        ->set('ux_image.provider', ProviderInterface::class)
            ->factory([service('ux_image.provider_resolver'), 'fromString'])
            ->args([
                abstract_arg('provider dsn'),
            ])

        ->set('ux_image.layout_resolver', LayoutResolver::class)
            ->args([
                abstract_arg('resolutions'),
            ])

        ->set('ux_image.renderer', ImageRenderer::class)
            ->args([
                service('ux_image.provider'),
                service('ux_image.layout_resolver'),
                abstract_arg('formats'),
            ])

        ->set('ux_image.url_generator', ImageUrlGenerator::class)
            ->args([
                service('ux_image.provider'),
            ])

        ->alias(ImageUrlGenerator::class, 'ux_image.url_generator')

        ->alias(ImageRendererInterface::class, 'ux_image.renderer')

        ->set('ux_image.twig_extension', ImageExtension::class)
            ->tag('twig.extension')

        ->set('ux_image.twig_runtime', ImageRuntime::class)
            ->args([
                service('ux_image.renderer'),
                service('ux_image.url_generator'),
                service('twig'),
            ])
            ->tag('twig.runtime')
    ;
};
