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

use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\UX\Icons\EventListener\WarmIconCacheOnAssetCompileListener;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_icons.event_listener.warm_icon_cache_on_asset_compile', WarmIconCacheOnAssetCompileListener::class)
            ->args([
                service('.ux_icons.cache_warmer'),
            ])
            ->tag('kernel.event_listener', [
                'event' => PreAssetsCompileEvent::class,
                'method' => '__invoke',
            ])
    ;
};
