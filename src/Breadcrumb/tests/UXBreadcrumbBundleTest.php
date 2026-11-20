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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
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

    public function testTheParentCrumbCheckerIsRegisteredInDebugModeOnly(): void
    {
        $debug = $this->container(true);
        $production = $this->container(false);

        $checker = $debug->getDefinition('ux_breadcrumb.parent_crumb_collector')->getArgument('$checker');

        self::assertTrue($debug->hasDefinition('ux_breadcrumb.parent_crumb_checker'));
        self::assertFalse($production->hasDefinition('ux_breadcrumb.parent_crumb_checker'));
        self::assertInstanceOf(Reference::class, $checker);
        self::assertSame('ux_breadcrumb.parent_crumb_checker', (string) $checker);
        self::assertSame(ContainerInterface::NULL_ON_INVALID_REFERENCE, $checker->getInvalidBehavior());
    }

    private function parentCrumbCollector(bool $debug): Definition
    {
        return $this->container($debug)->getDefinition('ux_breadcrumb.parent_crumb_collector');
    }

    private function container(bool $debug): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.debug' => $debug,
            'kernel.environment' => 'test',
            'kernel.build_dir' => sys_get_temp_dir(),
            'kernel.bundles' => [],
        ]));
        new UXBreadcrumbBundle()->getContainerExtension()?->load([], $container);

        return $container;
    }
}
