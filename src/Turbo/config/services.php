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

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;
use Symfony\UX\Turbo\Broadcaster\IdAccessor;
use Symfony\UX\Turbo\Broadcaster\ImuxBroadcaster;
use Symfony\UX\Turbo\Broadcaster\TwigBroadcaster;
use Symfony\UX\Turbo\Doctrine\BroadcastListener;
use Symfony\UX\Turbo\Request\RequestListener;
use Symfony\UX\Turbo\Twig\TurboRuntime;
use Symfony\UX\Turbo\Twig\TwigExtension;

/*
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set('turbo.broadcaster.imux', ImuxBroadcaster::class)
            ->args([tagged_iterator('turbo.broadcaster')])

        ->alias(BroadcasterInterface::class, 'turbo.broadcaster.imux')

        ->set('turbo.id_accessor', IdAccessor::class)
            ->args([
                service('property_accessor')->nullOnInvalid(),
                service('doctrine')->nullOnInvalid(),
            ])

        ->set('turbo.broadcaster.action_renderer', TwigBroadcaster::class)
            ->args([
                service('.inner'),
                service('twig'),
                abstract_arg('entity template prefixes'),
                service('turbo.id_accessor'),
            ])
            ->decorate('turbo.broadcaster.imux')

        ->set('turbo.twig.extension', TwigExtension::class)
            ->tag('twig.extension')

        ->set('turbo.twig.runtime', TurboRuntime::class)
            ->args([
                tagged_locator('turbo.renderer.stream_listen', 'transport'),
                abstract_arg('default_transport'),
            ])
            ->tag('twig.runtime')

        ->set('turbo.doctrine.event_listener', BroadcastListener::class)
            ->args([
                service('turbo.broadcaster.imux'),
                service('annotation_reader')->nullOnInvalid(),
            ])
            ->tag('doctrine.event_listener', ['event' => 'onFlush'])
            ->tag('doctrine.event_listener', ['event' => 'postFlush'])

        ->set('turbo.kernel.request_listener', RequestListener::class)
            ->tag('kernel.event_listener', ['event' => KernelEvents::REQUEST, 'priority' => 256])
    ;
};
