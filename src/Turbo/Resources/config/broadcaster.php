<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;
use Symfony\UX\Turbo\Broadcaster\TwigMercureBroadcaster;

/*
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            ->set('turbo.broadcaster.twig_mercure', TwigMercureBroadcaster::class)
            ->args([
                service('twig'),
                service('messenger.default_bus')->nullOnInvalid(),
                service('mercure.hub.default.publisher')->nullOnInvalid(),
            ])
            ->alias(BroadcasterInterface::class, 'turbo.broadcaster.twig_mercure')
    ;
};
