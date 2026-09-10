<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Routing;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Controller\UploadController;
use Symfony\UX\Upload\Routing\UploadRouteLoader;

final class UploadRouteLoaderTest extends TestCase
{
    #[Test]
    public function pathsAreRelativeToTheImportPrefix(): void
    {
        $loader = new UploadRouteLoader();
        $routes = $loader();

        $this->assertSame('/', $routes->get('ux_upload_direct')->getPath());
        $this->assertSame('/init', $routes->get('ux_upload_init')->getPath());
        $this->assertSame('/remove', $routes->get('ux_upload_remove')->getPath());
        $this->assertSame('/{uploadId}', $routes->get('ux_upload_handle')->getPath());
    }

    #[Test]
    public function routeNames(): void
    {
        $loader = new UploadRouteLoader();
        $routes = $loader();

        $this->assertNotNull($routes->get('ux_upload_direct'));
        $this->assertNotNull($routes->get('ux_upload_init'));
        $this->assertNotNull($routes->get('ux_upload_resume'));
        $this->assertNotNull($routes->get('ux_upload_remove'));
        $this->assertNotNull($routes->get('ux_upload_handle'));
        $this->assertCount(5, $routes);
    }

    #[Test]
    public function initRouteMethods(): void
    {
        $loader = new UploadRouteLoader();
        $routes = $loader();

        $this->assertSame(['POST'], $routes->get('ux_upload_init')->getMethods());
    }

    #[Test]
    public function directRouteMethods(): void
    {
        $routes = (new UploadRouteLoader())();

        $this->assertSame(['POST'], $routes->get('ux_upload_direct')->getMethods());
    }

    #[Test]
    public function handleRouteMethods(): void
    {
        $loader = new UploadRouteLoader();
        $routes = $loader();

        $this->assertSame(['GET', 'POST', 'PUT', 'DELETE'], $routes->get('ux_upload_handle')->getMethods());
    }

    #[Test]
    public function removeRouteMethods(): void
    {
        $routes = (new UploadRouteLoader())();

        $this->assertSame(['DELETE'], $routes->get('ux_upload_remove')->getMethods());
    }

    #[Test]
    public function controllerDefaults(): void
    {
        $loader = new UploadRouteLoader();
        $routes = $loader();

        $this->assertSame(UploadController::class.'::direct', $routes->get('ux_upload_direct')->getDefault('_controller'));
        $this->assertSame(UploadController::class.'::init', $routes->get('ux_upload_init')->getDefault('_controller'));
        $this->assertSame(UploadController::class.'::remove', $routes->get('ux_upload_remove')->getDefault('_controller'));
        $this->assertSame(UploadController::class.'::handle', $routes->get('ux_upload_handle')->getDefault('_controller'));
    }
}
