<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\DependencyInjection\Loader\Configurator;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\TwigComponent\DataCollector\TwigComponentDataCollector;
use Symfony\UX\TwigComponent\EventListener\TwigComponentLoggerListener;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $container->services()

        ->set('ux.twig_component.component_logger_listener', TwigComponentLoggerListener::class)
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
        ]);
};
