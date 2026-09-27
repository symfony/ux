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

use League\Glide\Server;
use League\Glide\Signatures\SignatureInterface;
use Symfony\UX\Image\Bridge\Glide\Controller\GlideController;
use Symfony\UX\Image\Bridge\Glide\ServerFactory;
use Symfony\UX\Image\Bridge\Glide\SignatureFactory;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('ux_image.glide.server', Server::class)
            ->factory([ServerFactory::class, 'createFromDsn'])
            ->args([param('ux_image.provider_dsn')])

        ->set('ux_image.glide.signature', SignatureInterface::class)
            ->factory([SignatureFactory::class, 'createFromDsn'])
            ->args([param('ux_image.provider_dsn'), param('kernel.environment')])

        ->set(GlideController::class)
            ->args([
                '$server' => service('ux_image.glide.server'),
                '$signature' => service('ux_image.glide.signature'),
                '$supportedFormats' => param('ux_image.formats'),
            ])
            ->tag('controller.service_arguments')
    ;
};
