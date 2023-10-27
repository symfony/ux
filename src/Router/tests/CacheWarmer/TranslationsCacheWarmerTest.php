<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests\CacheWarmer;

use PHPUnit\Framework\TestCase;

final class RoutesCacheWarmerTest extends TestCase
{
    protected static $cacheDir;

    public static function setUpBeforeClass(): void
    {
        self::$cacheDir = tempnam(sys_get_temp_dir(), 'sf_cache_warmer_dir');
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$cacheDir);
    }

    public function test()
    {
        // TODO
    }
}
