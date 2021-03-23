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
use Symfony\UX\Turbo\Doctrine\BroadcastListener;

/*
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            ->set(BroadcastListener::class)
            ->args([service(BroadcasterInterface::class)])
            ->tag('doctrine.event_listener', ['event' => 'onFlush'])
            ->tag('doctrine.event_listener', ['event' => 'postFlush'])
    ;
};
