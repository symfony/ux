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

use Symfony\UX\Image\Twig\UXImgComponent;
use Symfony\UX\Image\Twig\UXPictureComponent;
use Symfony\UX\Image\Twig\UXSourceComponent;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_image.twig_component.img', UXImgComponent::class)
            ->tag('twig.component', ['key' => 'ux:img'])

        ->set('.ux_image.twig_component.picture', UXPictureComponent::class)
            ->tag('twig.component', [
                'key' => 'ux:picture',
                'template' => '@UXImage/components/ux/picture.html.twig',
            ])

        ->set('.ux_image.twig_component.source', UXSourceComponent::class)
            ->tag('twig.component', ['key' => 'ux:source'])
    ;
};
