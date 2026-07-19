<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Registry;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Registry\LocalRegistry;

final class LocalRegistryTest extends KernelTestCase
{
    public function testCanGetKit()
    {
        $localRegistry = new LocalRegistry(
            self::getContainer()->get('ux_toolkit.kit.kit_factory'),
            self::getContainer()->get('filesystem'),
        );

        $kit = $localRegistry->getKit('shadcn');

        $this->assertInstanceOf(Kit::class, $kit);
        $this->assertSame('Shadcn UI', $kit->manifest->name);
    }

    public function testExists()
    {
        $this->assertTrue(LocalRegistry::exists('shadcn'));
        $this->assertFalse(LocalRegistry::exists('does-not-exist'));
        // A traversal attempt is rejected by the name guard, never hitting the filesystem.
        $this->assertFalse(LocalRegistry::exists('../../etc'));
    }

    public function testGetAvailableKits()
    {
        $localRegistry = new LocalRegistry(
            self::getContainer()->get('ux_toolkit.kit.kit_factory'),
            self::getContainer()->get('filesystem'),
        );

        $kits = $localRegistry->getAvailableKits();

        $this->assertSame(LocalRegistry::getAvailableKitsName(), array_keys($kits));
        $this->assertContainsOnlyInstancesOf(Kit::class, $kits);
        $this->assertSame('Shadcn UI', $kits['shadcn']->manifest->name);
    }
}
