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

use Symfony\UX\Icons\Command\DumpIconsCommand;
use Symfony\UX\Icons\Finder\JavaScriptIconFinder;
use Symfony\UX\Icons\IconRenderer;
use Symfony\UX\Icons\JavaScript\IconsDumper;
use Symfony\UX\Icons\JavaScript\IconsDumperCacheWarmer;

return static function (ContainerConfigurator $container): void {
    $container->services()
        // Local icons only: dumping must never trigger an on-demand download
        ->set('.ux_icons.javascript.icon_renderer', IconRenderer::class)
            ->args([
                service('.ux_icons.local_svg_icon_registry'),
                abstract_arg('default_icon_attributes'),
                abstract_arg('icon_aliases'),
                abstract_arg('icon_set_attributes'),
                abstract_arg('icon_suffix_attributes'),
            ])

        ->set('.ux_icons.javascript.dumper', IconsDumper::class)
            ->args([
                service('.ux_icons.javascript.icon_renderer'),
                abstract_arg('icon_dir'),
                abstract_arg('icon_set_paths'),
                abstract_arg('icon_aliases'),
                abstract_arg('dump_directory'),
                abstract_arg('dump_typescript'),
            ])

        ->set('.ux_icons.javascript.cache_warmer', IconsDumperCacheWarmer::class)
            ->args([
                service('.ux_icons.javascript.dumper'),
            ])
            ->tag('kernel.cache_warmer')

        ->set('.ux_icons.javascript.icon_finder', JavaScriptIconFinder::class)
            ->args([
                abstract_arg('scan_directories'),
            ])
            ->tag('ux_icons.finder')

        ->set('.ux_icons.command.dump', DumpIconsCommand::class)
            ->args([
                service('.ux_icons.javascript.dumper'),
            ])
            ->tag('console.command')
    ;
};
