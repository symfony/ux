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

use Symfony\UX\DataTable\DataTableFactory;
use Symfony\UX\DataTable\DataTableType;
use Symfony\UX\DataTable\Twig\DataTableExtension;
use Symfony\UX\DataTable\TypeRegistry;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->private()
    ;

    $services->set('ux_data_table.type_registry', TypeRegistry::class)
        ->arg('$locator', tagged_locator('ux_data_table.type'))
    ;

    $services->set('ux_data_table.factory', DataTableFactory::class)
        ->arg('$paginator', service('ux_pagination.paginator'))
        ->arg('$formFactory', service('form.factory'))
        ->arg('$registry', service('ux_data_table.type_registry'))
        ->arg('$urlGenerator', service('router')->nullOnInvalid())
        ->arg('$propertyAccessor', service('property_accessor')->nullOnInvalid())
    ;
    $services->alias(DataTableFactory::class, 'ux_data_table.factory');

    // Types are fetched by class from the registry locator
    $services->set(DataTableType::class)
        ->tag('ux_data_table.type')
    ;

    $services->set('ux_data_table.twig.extension', DataTableExtension::class)
        ->arg('$csrfTokenManager', service('security.csrf.token_manager')->nullOnInvalid())
        ->autoconfigure()
    ;
};
