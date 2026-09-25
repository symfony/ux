<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Icons\Icon;
use Zenstruck\Console\Test\InteractsWithConsole;

final class CacheWarmupTest extends KernelTestCase
{
    use InteractsWithConsole;

    public function testCacheWarmupWarmsLocalIconsOnly(): void
    {
        $pool = self::getContainer()->get('test.ux_icons.cache');
        $pool->clear();

        $this->executeConsoleCommand('cache:warmup')->assertSuccessful();

        $this->assertTrue($pool->hasItem(Icon::nameToId('user')));
        $this->assertTrue($pool->hasItem(Icon::nameToId('sub:check')));

        $this->assertFalse($pool->hasItem(Icon::nameToId('lucide:mail')));
        $this->assertFalse($pool->hasItem('iconify-sets'));
    }
}
