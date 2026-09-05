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

return function (RoutingConfigurator $routes) {
    $routes->add('ux_autocomplete', '/{alias}')
        ->controller('ux.autocomplete.autocomplete_controller')
    ;

    $routes->alias('ux_entity_autocomplete', 'ux_autocomplete')
        ->deprecate('symfony/ux-autocomplete', '2.24', 'The "%alias_id%" route alias is deprecated, use "ux_autocomplete" instead.')
    ;
};
