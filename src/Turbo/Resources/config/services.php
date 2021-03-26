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

use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;
use Symfony\UX\Turbo\Broadcaster\ImuxBroadcaster;
use Symfony\UX\Turbo\Doctrine\BroadcastListener;
use Symfony\UX\Turbo\Stream\AddTurboStreamFormatSubscriber;
use Symfony\UX\Turbo\Twig\TwigExtension;

/*
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(AddTurboStreamFormatSubscriber::class)
            ->tag('kernel.event_subscriber')

        ->set(ImuxBroadcaster::class)
            ->args([tagged_iterator('turbo.broadcaster')])

        ->alias(BroadcasterInterface::class, ImuxBroadcaster::class)

        ->set(TwigExtension::class)
            ->args([tagged_locator('turbo.renderer.stream_listen', 'index'), abstract_arg('default')])
            ->tag('twig.extension')

        ->set(BroadcastListener::class)
            ->args([service(BroadcasterInterface::class)])
            ->tag('doctrine.event_listener', ['event' => 'onFlush'])
            ->tag('doctrine.event_listener', ['event' => 'postFlush'])
    ;
};
