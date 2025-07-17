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
    public function testCanGetKit(): void
    {
        $localRegistry = new LocalRegistry(
            self::getContainer()->get('ux_toolkit.kit.kit_factory'),
            self::getContainer()->get('filesystem'),
            self::getContainer()->getParameter('kernel.project_dir'),
        );

        $kit = $localRegistry->getKit('shadcn');

        $this->assertInstanceOf(Kit::class, $kit);
        $this->assertSame('Shadcn UI', $kit->name);
    }
}
