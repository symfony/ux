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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Toolkit\Component\ComponentDocParser;
use Symfony\UX\Toolkit\UXToolkitBundle;

class UXToolkitBundleTest extends KernelTestCase
{
    public function testBundleBuildsSuccessfully()
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();

        $this->assertInstanceOf(UXToolkitBundle::class, $container->get('kernel')->getBundles()['UXToolkitBundle']);
    }

    public function testComponentDocParserIsRegisteredAsAPublicService()
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();

        $this->assertInstanceOf(ComponentDocParser::class, $container->get('ux_toolkit.component.component_doc_parser'));
    }
}
