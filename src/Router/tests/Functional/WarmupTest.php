<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Router\Tests\ParsesDumpedRoutesTrait;

final class WarmupTest extends TestCase
{
    use ParsesDumpedRoutesTrait;

    public function testCacheClearDumpsExposedRoutes(): void
    {
        $dumpDir = __DIR__.'/../../var/routes';

        self::assertFileExists($dumpDir.'/index.d.ts');
        $routes = self::parseDumpedRoutes($dumpDir.'/index.js');

        self::assertArrayHasKey('app_home', $routes);
        self::assertArrayHasKey('legacy_search', $routes);
        self::assertArrayNotHasKey('app_admin_dashboard', $routes);
        self::assertArrayNotHasKey('app_private', $routes);
        self::assertArrayNotHasKey('internal_hidden', $routes);
    }
}
