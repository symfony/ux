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

use Symfony\UX\Turbo\Mercure\Broadcaster;
use Symfony\UX\Turbo\Mercure\TurboStreamListenRenderer;

/*
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            ->set(Broadcaster::class)
            ->abstract()
            ->args([
                abstract_arg('name'),
                service('twig'),
                abstract_arg('publisher'),
                service('property_accessor'),
                null,
                abstract_arg('entity namespace'),
            ])

            ->set(TurboStreamListenRenderer::class)
            ->abstract()
            ->args([
                abstract_arg('hub'),
                service('webpack_encore.twig_stimulus_extension'),
                service('property_accessor'),
            ])
    ;
};
