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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Router\Tests\Kernel\EntityRoutesAppKernel;

final class CacheWarmupCommandTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/router_bundle/'.uniqid('cache_warmup_', true);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->tmpDir);
    }

    public function testRunsTheCacheWarmersInPriorityOrderWithoutADumpDirectory(): void
    {
        $kernel = new EntityRoutesAppKernel($this->tmpDir, 'test');
        $application = new Application($kernel);
        $tester = new CommandTester($application->find('cache:warmup'));

        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertFileExists($kernel->getDumpDir().'/index.js');
    }
}
