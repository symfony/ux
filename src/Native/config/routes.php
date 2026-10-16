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

return static function (RoutingConfigurator $routes): void {
    $routes->add('ux_native_recede_historical_location', '/recede_historical_location')
        ->controller('ux_native.controller.historical_location::recede')
        ->methods(['GET'])
    ;
    $routes->add('ux_native_refresh_historical_location', '/refresh_historical_location')
        ->controller('ux_native.controller.historical_location::refresh')
        ->methods(['GET'])
    ;
    $routes->add('ux_native_resume_historical_location', '/resume_historical_location')
        ->controller('ux_native.controller.historical_location::resume')
        ->methods(['GET'])
    ;
};
