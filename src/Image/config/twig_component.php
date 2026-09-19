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

use Symfony\UX\Image\Twig\Components\Image;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('ux_image.twig.component', Image::class)
        ->tag('twig.component', ['key' => 'ux:image'])
    ;
};
