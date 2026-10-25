<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\UX\Breadcrumb\UXBreadcrumbBundle;

#[CoversClass(UXBreadcrumbBundle::class)]
final class UXBreadcrumbBundleTest extends TestCase
{
    public function testGetContainerExtensionReturnsExtensionWithCorrectAlias(): void
    {
        $bundle = new UXBreadcrumbBundle();
        $extension = $bundle->getContainerExtension();

        self::assertNotNull($extension);
        self::assertSame('ux_breadcrumb', $extension->getAlias());
    }

    public function testGetPathReturnsParentDirectory(): void
    {
        $bundle = new UXBreadcrumbBundle();
        $path = $bundle->getPath();

        self::assertSame(\dirname(__DIR__), $path);
        self::assertDirectoryExists($path);
        self::assertFileExists($path.'/composer.json');
    }

    public function testTheParentCrumbCollectorIsResetBetweenRequestsInDebugModeOnly(): void
    {
        self::assertTrue($this->parentCrumbCollector(true)->hasTag('kernel.reset'));
        self::assertFalse($this->parentCrumbCollector(false)->hasTag('kernel.reset'));
    }

    private function parentCrumbCollector(bool $debug): Definition
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.debug' => $debug,
            'kernel.environment' => 'test',
            'kernel.build_dir' => sys_get_temp_dir(),
            'kernel.bundles' => [],
        ]));
        new UXBreadcrumbBundle()->getContainerExtension()?->load([], $container);

        return $container->getDefinition('ux_breadcrumb.parent_crumb_collector');
    }
}
