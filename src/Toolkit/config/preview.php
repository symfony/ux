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

use Symfony\UX\Toolkit\Preview\PreviewAssetsCacheWarmer;
use Symfony\UX\Toolkit\Preview\PreviewAssetsGenerator;
use Symfony\UX\Toolkit\Preview\PreviewImportMapConfigReader;
use Symfony\UX\Toolkit\Preview\PreviewKitRegistry;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_toolkit.preview.kit_registry', PreviewKitRegistry::class)
            ->args([
                service('.ux_toolkit.kit.kit_factory'),
                abstract_arg('kit dirs, set by PreviewPass'),
            ])

        ->set('.ux_toolkit.preview.assets_generator', PreviewAssetsGenerator::class)
            ->args([
                service('asset_mapper.importmap.remote_package_storage'),
                service('filesystem'),
            ])

        ->set('.ux_toolkit.preview.cache_warmer', PreviewAssetsCacheWarmer::class)
            ->args([
                service('.ux_toolkit.preview.kit_registry'),
                service('.ux_toolkit.preview.assets_generator'),
                service('filesystem'),
                abstract_arg('output dir, set by PreviewPass'),
            ])
            ->tag('kernel.cache_warmer')

        ->set('.ux_toolkit.preview.importmap_config_reader', PreviewImportMapConfigReader::class)
            ->decorate('asset_mapper.importmap.config_reader')
            ->args([
                service('.inner'),
                abstract_arg('entrypoints, set by PreviewPass'),
                abstract_arg('importmap.php path, set by PreviewPass'),
                service('asset_mapper.importmap.remote_package_storage'),
            ])
    ;
};
