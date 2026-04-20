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
use Symfony\UX\Editor\Upload\EditorUploadController;

return static function (RoutingConfigurator $r): void {
    $r->add('ux_editor_upload', '/_ux_editor/upload/{field}')
        ->controller(EditorUploadController::class)
        ->methods(['POST'])
        ->requirements(['field' => '[a-zA-Z0-9_.-]+']);
};
