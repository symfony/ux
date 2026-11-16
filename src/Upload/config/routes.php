<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Upload\Routing\UploadRouteLoader;

return static function (RoutingConfigurator $routes): void {
    $routes->import(UploadRouteLoader::class, 'service');
};
