<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\UX\Turbo\Twig\FrameExtension;
use Symfony\UX\Turbo\Twig\StreamExtension;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            ->set('turbo.twig.extension.frame', FrameExtension::class)
            ->tag('twig.extension')

            ->set('turbo.twig.extension.stream', StreamExtension::class)
            ->args([param('turbo.mercure.subscribe_url')])
            ->tag('twig.extension')
    ;
};
