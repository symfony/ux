<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->add('ux_live_component', '/{component}/{action}')
        ->defaults([
            'action' => 'get',
        ])
    ;
};
