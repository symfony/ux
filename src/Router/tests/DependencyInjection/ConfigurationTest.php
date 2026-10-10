<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\UX\Router\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    public function testDefaultConfiguration(): void
    {
        self::assertSame([
            'dump_directory' => '%kernel.project_dir%/var/routes',
            'dump_typescript' => true,
            'routes' => [],
        ], $this->process([]));
    }

    public function testRoutesAsString(): void
    {
        self::assertSame(['app_*'], $this->process([['routes' => 'app_*']])['routes']);
    }

    public function testRoutesAsList(): void
    {
        self::assertSame(['app_*', '!app_admin_*'], $this->process([['routes' => ['app_*', '!app_admin_*']]])['routes']);
    }

    /**
     * @param list<array<string, mixed>> $configs
     *
     * @return array<string, mixed>
     */
    private function process(array $configs): array
    {
        return new Processor()->processConfiguration(new Configuration(), $configs);
    }
}
