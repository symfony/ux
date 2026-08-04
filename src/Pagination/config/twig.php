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

use Symfony\UX\Pagination\Twig\PaginationExtension;
use Symfony\UX\Pagination\Twig\PaginationRenderer;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->private()
    ;

    $services->set('ux_pagination.renderer', PaginationRenderer::class)
        ->arg('$twig', service('twig'))
    ;

    $services->set('ux_pagination.twig.extension', PaginationExtension::class)
        ->arg('$renderer', service('ux_pagination.renderer'))
        ->autoconfigure()
        ->tag('ux.twig_component.twig_renderer', ['key' => 'ux:pagination'])
    ;
};
