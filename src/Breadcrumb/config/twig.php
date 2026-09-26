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

use Symfony\UX\Breadcrumb\Twig\BreadcrumbExtension;
use Symfony\UX\Breadcrumb\Twig\BreadcrumbRenderer;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->private()
    ;

    $services->set('ux_breadcrumb.renderer', BreadcrumbRenderer::class)
        ->arg('$twig', service('twig'))
        ->arg('$defaultTheme', abstract_arg('default theme'))
    ;

    $services->set('ux_breadcrumb.twig.extension', BreadcrumbExtension::class)
        ->args([
            service('ux_breadcrumb.trail_provider'),
            service('ux_breadcrumb.resolver'),
            service('ux_breadcrumb.renderer'),
            service('translator')->nullOnInvalid(),
        ])
        ->autoconfigure()
    ;
};
