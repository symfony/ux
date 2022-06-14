<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->add('ux_entity_autocomplete', '/{alias}')
        ->controller('ux.autocomplete.entity_autocomplete_controller')
    ;
};
