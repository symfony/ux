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

use Symfony\UX\Native\Controller\HistoricalLocationController;
use Symfony\UX\Native\EventListener\NativeListener;
use Symfony\UX\Native\Navigation\NativeNavigator;
use Symfony\UX\Native\Twig\NativeExtension;
use Symfony\UX\Native\Command\BuildConfigurationsCommand;
use Symfony\UX\Native\ConfigurationBuilder;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $container->services()
        ->set('.ux_native.listener.native_listener', NativeListener::class)
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest'])
    ;

    $container->services()
        ->set('ux_native.twig.native_extension', NativeExtension::class)
        ->args([service('request_stack')])
        ->tag('twig.extension')
    ;

    $services->set('ux_native.navigator', NativeNavigator::class)
        ->args([service('request_stack'), service('router')])
    ;
    $services->alias(NativeNavigator::class, 'ux_native.navigator');

    $services->set('ux_native.controller.historical_location', HistoricalLocationController::class)
        ->public()
        ->tag('controller.service_arguments')
    ;

    $services->set('.ux_native.configuration_builder', ConfigurationBuilder::class)
        ->args([
            '%ux_native.output_dir%',
        ])
    ;
    $services->set('.ux_native.command.build_configurations', BuildConfigurationsCommand::class)
        ->args([service('.ux_native.configuration_builder')])
        ->tag('console.command')
    ;
};
