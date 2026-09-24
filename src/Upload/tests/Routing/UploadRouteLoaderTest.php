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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Controller\UploadController;
use Symfony\UX\Upload\Routing\UploadRouteLoader;

#[CoversClass(UploadRouteLoader::class)]
final class UploadRouteLoaderTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, list<string>, string}>
     */
    public static function routes(): iterable
    {
        yield 'direct' => ['ux_upload_direct', '/', ['POST'], 'direct'];
        yield 'init' => ['ux_upload_init', '/init', ['POST'], 'init'];
        yield 'resume' => ['ux_upload_resume', '/resume', ['POST'], 'resume'];
        yield 'remove' => ['ux_upload_remove', '/remove', ['DELETE'], 'remove'];
        yield 'handle' => ['ux_upload_handle', '/{uploadId}', ['GET', 'POST', 'PUT', 'DELETE'], 'handle'];
    }

    /**
     * @param list<string> $methods
     */
    #[DataProvider('routes')]
    public function testRouteIsRelativeToTheImportPrefix(string $name, string $path, array $methods, string $action): void
    {
        $route = (new UploadRouteLoader())()->get($name);

        self::assertNotNull($route);
        self::assertSame($path, $route->getPath());
        self::assertSame($methods, $route->getMethods());
        self::assertSame(UploadController::class.'::'.$action, $route->getDefault('_controller'));
    }

    public function testLoadsNoOtherRoute(): void
    {
        self::assertCount(5, (new UploadRouteLoader())());
    }
}
