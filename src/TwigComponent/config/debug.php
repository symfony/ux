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

use Symfony\UX\TwigComponent\Command\TwigComponentDebugCommand;
use Symfony\UX\TwigComponent\DataCollector\TwigComponentDataCollector;
use Symfony\UX\TwigComponent\EventListener\TwigComponentLoggerListener;

return static function (ContainerConfigurator $container) {
    $container->services()

        ->set('ux.twig_component.component_logger_listener', TwigComponentLoggerListener::class)
        ->args([
            service('debug.stopwatch')->ignoreOnInvalid(),
        ])
        ->tag('kernel.event_subscriber')

        ->set('ux.twig_component.data_collector', TwigComponentDataCollector::class)
        ->args([
            service('ux.twig_component.component_logger_listener'),
            service('twig'),
        ])
        ->tag('data_collector', [
            'template' => '@TwigComponent/Collector/twig_component.html.twig',
            'id' => 'twig_component',
            'priority' => 256,
        ])

        ->set('ux.twig_component.command.debug', TwigComponentDebugCommand::class)
        ->args([
            param('twig.default_path'),
            service('ux.twig_component.component_factory'),
            service('twig'),
            param('ux.twig_component.class_component_map'),
            param('ux.twig_component.anonymous_template_directory'),
        ])
        ->tag('console.command')
    ;
};
