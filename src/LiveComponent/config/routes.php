<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->add('ux_live_component', '/{_live_component}/{_live_action}')
        ->defaults([
            'action' => 'get',
        ])
    ;
};
