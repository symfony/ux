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
use Symfony\UX\DesignTokens\EventListener\WarmDesignTokensOnAssetCompileListener;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_design_tokens.asset_compile_listener', WarmDesignTokensOnAssetCompileListener::class)
            ->args([
                service('.ux_design_tokens.cache_warmer'),
                param('kernel.build_dir'),
            ])
            ->tag('kernel.event_listener', [
                'event' => PreAssetsCompileEvent::class,
                'method' => '__invoke',
            ])
    ;
};
