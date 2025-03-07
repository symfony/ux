<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Toolkit\DependencyInjection\ToolkitExtension;
use Symfony\UX\Toolkit\UxToolkitBundle;

/**
 * @author Jean-François Lépine
 */
class UxToolkitBundleTest extends KernelTestCase
{
    public function testBundleBuildsSuccessfully(): void
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();

        $this->assertInstanceOf(UxToolkitBundle::class, $container->get('kernel')->getBundles()['UxToolkitBundle']);
    }
}
