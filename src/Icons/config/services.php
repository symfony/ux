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

use Symfony\UX\Icons\AriaHiddenPreRenderer;
use Symfony\UX\Icons\Command\WarmCacheCommand;
use Symfony\UX\Icons\IconCacheWarmer;
use Symfony\UX\Icons\IconRenderer;
use Symfony\UX\Icons\Registry\CacheIconRegistry;
use Symfony\UX\Icons\Registry\ChainIconRegistry;
use Symfony\UX\Icons\Registry\LocalSvgIconRegistry;
use Symfony\UX\Icons\Twig\IconFinder;
use Symfony\UX\Icons\Twig\UXIconExtension;
use Symfony\UX\Icons\Twig\UXIconRuntime;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_icons.cache_icon_registry', CacheIconRegistry::class)
            ->args([
                service('.ux_icons.chain_registry'),
                service('cache.system'),
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

        ->set('.ux_icons.icon_renderer', IconRenderer::class)
            ->args([
                service('.ux_icons.icon_registry'),
                abstract_arg('default_icon_attributes'),
                abstract_arg('icon_aliases'),
                tagged_iterator('ux_icons.icon_pre_renderer'),
            ])

        ->alias('Symfony\UX\Icons\IconRendererInterface', '.ux_icons.icon_renderer')

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

        ->set('.ux_icons.aria_hidden_pre_renderer', AriaHiddenPreRenderer::class)
            ->tag('ux_icons.icon_pre_renderer')
    ;
};
