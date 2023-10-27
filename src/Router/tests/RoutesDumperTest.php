<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Router\RoutesDumper;

class RoutesDumperTest extends TestCase
{
    protected static $routesDumpDir;

    public static function setUpBeforeClass(): void
    {
        self::$routesDumpDir = sys_get_temp_dir().'/sf_ux_router/'.uniqid('routes', true);
    }

    public static function tearDownAfterClass(): void
    {
        @rmdir(self::$routesDumpDir);
    }

    public function testDump(): void
    {
        $routesDumper = new RoutesDumper(
            self::$routesDumpDir,
            new Filesystem(),
        );

        $routesDumper->dump(
            // TODO
        );

        $this->assertFileExists(self::$routesDumpDir.'/index.js');
        $this->assertFileExists(self::$routesDumpDir.'/index.d.ts');

        $this->assertStringEqualsFile(self::$routesDumpDir.'/index.js', <<<'JAVASCRIPT'
// TODO
JAVASCRIPT);

        $this->assertStringEqualsFile(self::$routesDumpDir.'/index.d.ts', <<<'TYPESCRIPT'
// TODO
TYPESCRIPT);
    }
}
