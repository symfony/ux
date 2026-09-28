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

use Symfony\UX\Image\Twig\ImageExtension;
use Symfony\UX\Image\Twig\ImageRuntime;

return static function (ContainerConfigurator $container): void {
    $container->services()
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
