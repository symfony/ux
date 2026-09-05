<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Image\Bridge\Glide\Controller\GlideController;

return function (RoutingConfigurator $routes) {
    $routes->add('ux_image_glide', '/{path}')
        ->controller(GlideController::class)
        ->requirements(['path' => '.+'])
        ->methods(['GET', 'HEAD'])
    ;
};
