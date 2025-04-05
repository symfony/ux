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

use Symfony\UX\Icons\Command\ImportIconCommand;
use Symfony\UX\Icons\Command\LockIconsCommand;
use Symfony\UX\Icons\Command\SearchIconCommand;
use Symfony\UX\Icons\Command\WarmCacheCommand;
use Symfony\UX\Icons\IconCacheWarmer;
use Symfony\UX\Icons\Iconify;
use Symfony\UX\Icons\IconRenderer;
use Symfony\UX\Icons\IconRendererInterface;
use Symfony\UX\Icons\Registry\CacheIconRegistry;
use Symfony\UX\Icons\Registry\ChainIconRegistry;
use Symfony\UX\Icons\Registry\IconifyOnDemandRegistry;
use Symfony\UX\Icons\Registry\LocalSvgIconRegistry;
use Symfony\UX\Icons\Twig\IconFinder;
use Symfony\UX\Icons\Twig\UXIconExtension;
use Symfony\UX\Icons\Twig\UXIconRuntime;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_icons.cache')
            ->parent('cache.system')
            ->private()
            ->tag('cache.pool')

        ->set('.ux_icons.cache_icon_registry', CacheIconRegistry::class)
            ->args([
                service('.ux_icons.chain_registry'),
                service('.ux_icons.cache'),
            ])

        ->set('.ux_icons.local_svg_icon_registry', LocalSvgIconRegistry::class)
            ->args([
                abstract_arg('icon_dir'),
            ])
            ->tag('ux_icons.registry', ['priority' => 10])

        ->set('.ux_icons.chain_registry', ChainIconRegistry::class)
            ->args([
                tagged_iterator('ux_icons.registry'),
            ])

        ->alias('.ux_icons.icon_registry', '.ux_icons.cache_icon_registry')

        ->set('.ux_icons.twig_icon_extension', UXIconExtension::class)
            ->tag('twig.extension')

        ->set('.ux_icons.twig_icon_runtime', UXIconRuntime::class)
            ->args([
                service('.ux_icons.icon_renderer'),
                abstract_arg('ignore_not_found'),
                service('logger')->ignoreOnInvalid(),
            ])
            ->tag('twig.runtime')
            ->tag('ux.twig_component.twig_renderer', ['key' => 'ux:icon'])

        ->set('.ux_icons.icon_renderer', IconRenderer::class)
            ->args([
                service('.ux_icons.icon_registry'),
                abstract_arg('default_icon_attributes'),
                abstract_arg('icon_aliases'),
            ])

        ->alias(IconRendererInterface::class, '.ux_icons.icon_renderer')

        ->set('.ux_icons.icon_finder', IconFinder::class)
            ->args([
                service('twig'),
                abstract_arg('icon_dir'),
            ])

        ->set('.ux_icons.cache_warmer', IconCacheWarmer::class)
            ->args([
                service('.ux_icons.cache_icon_registry'),
                service('.ux_icons.icon_finder'),
            ])

        ->set('.ux_icons.command.warm_cache', WarmCacheCommand::class)
            ->args([
                service('.ux_icons.cache_warmer'),
            ])
            ->tag('console.command')

        ->set('.ux_icons.iconify', Iconify::class)
            ->args([
                service('.ux_icons.cache'),
                abstract_arg('endpoint'),
                service('http_client')->nullOnInvalid(),
            ])

        ->set('.ux_icons.iconify_on_demand_registry', IconifyOnDemandRegistry::class)
            ->args([
                service('.ux_icons.iconify'),
            ])
            ->tag('ux_icons.registry', ['priority' => -10])

        ->set('.ux_icons.command.import', ImportIconCommand::class)
            ->args([
                service('.ux_icons.iconify'),
                service('.ux_icons.local_svg_icon_registry'),
            ])
            ->tag('console.command')

        ->set('.ux_icons.command.lock', LockIconsCommand::class)
            ->args([
                service('.ux_icons.iconify'),
                service('.ux_icons.local_svg_icon_registry'),
                service('.ux_icons.icon_finder'),
            ])
            ->tag('console.command')

        ->set('.ux_icons.command.search', SearchIconCommand::class)
            ->args([
                service('.ux_icons.iconify'),
            ])
            ->tag('console.command')
    ;
};
