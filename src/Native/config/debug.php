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

use Symfony\UX\Native\ConfigurationBuilder;
use Symfony\UX\Native\EventListener\DevServerEventListener;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('.ux_native.listener.dev_server_listener', DevServerEventListener::class)
        ->args([
            service('.ux_native.configuration_builder'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest'])
    ;
};
