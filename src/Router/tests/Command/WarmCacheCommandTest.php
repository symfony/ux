<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\UX\Router\CacheWarmer\RoutesCacheWarmer;
use Symfony\UX\Router\Command\WarmCacheCommand;

final class WarmCacheCommandTest extends TestCase
{
    public function testWarmCache(): void
    {
        $cacheDir = '/tmp/cache';
        $cacheWarmer = $this->createMock(RoutesCacheWarmer::class);
        $cacheWarmer
            ->expects($this->once())
            ->method('warmUp')
            ->with($cacheDir);

        $application = new Application();
        $application->addCommand(new WarmCacheCommand($cacheWarmer, $cacheDir));

        $tester = new CommandTester($application->find('ux:router:warm-cache'));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Warming the routes cache...', $tester->getDisplay());
        $this->assertStringContainsString('Routes cache warmed.', $tester->getDisplay());
    }
}
