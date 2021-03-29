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

use Symfony\UX\Turbo\Mercure\Broadcaster;
use Symfony\UX\Turbo\Mercure\TurboStreamListenRenderer;

/*
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set('turbo.broadcaster.mercure', Broadcaster::class)
            ->abstract()
            ->args([
                abstract_arg('name'),
                abstract_arg('publisher'),
            ])

        ->set('turbo.stream_listen_renderer.mercure', TurboStreamListenRenderer::class)
            ->abstract()
            ->args([
                abstract_arg('hub'),
                service('webpack_encore.twig_stimulus_extension'),
                service('turbo.id_accessor'),
            ])
    ;
};
