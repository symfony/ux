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

use Symfony\UX\Icons\Command\ImportIconCommand;
use Symfony\UX\Icons\Command\LockIconsCommand;
use Symfony\UX\Icons\Command\SearchIconCommand;
use Symfony\UX\Icons\Iconify;
use Symfony\UX\Icons\Registry\IconifyOnDemandRegistry;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_icons.iconify_on_demand_registry', IconifyOnDemandRegistry::class)
            ->args([
                service('.ux_icons.iconify'),
            ])
            ->tag('ux_icons.registry')

        ->set('.ux_icons.iconify', Iconify::class)
            ->args([
                service('cache.system'),
                abstract_arg('endpoint'),
                service('http_client')->nullOnInvalid(),
            ])

        ->set('.ux_icons.command.import', ImportIconCommand::class)
            ->args([
                service('.ux_icons.iconify'),
                service('.ux_icons.local_svg_icon_registry'),
            ])
            ->tag('console.command')

        ->set('.ux_icons.command.lock', LockIconsCommand::class)
            ->args([
                service('.ux_icons.iconify'),
                service('.ux_icons.local_svg_icon_registry'),
                service('.ux_icons.icon_finder'),
            ])
            ->tag('console.command')

        ->set('.ux_icons.command.search', SearchIconCommand::class)
            ->args([
                service('.ux_icons.iconify'),
            ])
            ->tag('console.command')
    ;
};
