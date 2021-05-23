<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Notify\DependencyInjection\Loader\Configurator;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use Symfony\UX\Notify\Twig\NotifyExtension;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->private()
        ->set('twig.extension.notify', NotifyExtension::class)
        ->args([
            param('mercure.default_hub'),
        ])
        ->tag('twig.extension')
    ;
};
