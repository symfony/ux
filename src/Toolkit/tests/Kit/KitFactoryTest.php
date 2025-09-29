<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Kit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Toolkit\Kit\KitFactory;

final class KitFactoryTest extends KernelTestCase
{
    public function testShouldFailIfPathIsNotAbsolute()
    {
        $kitFactory = $this->createKitFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Path "shadcn" is not absolute.');

        $kitFactory->createKitFromAbsolutePath('shadcn');
    }

    public function testShouldFailIfKitDoesNotExist()
    {
        $kitFactory = $this->createKitFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Path "%s" does not exist.', __DIR__.'/../../kits/does-not-exist'));

        $kitFactory->createKitFromAbsolutePath(__DIR__.'/../../kits/does-not-exist');
    }

    public function testCanCreateShadcnKit()
    {
        $kit = $this->createKitFactory()->createKitFromAbsolutePath(__DIR__.'/../../kits/shadcn');

        $this->assertNotNull($kit);
        $this->assertNotEmpty($kit->getRecipes());

        foreach ($kit->getRecipes() as $recipe) {
            $this->assertNotEmpty($recipe->absolutePath);
            $this->assertNotEmpty($recipe->manifest->name);
            $this->assertNotEmpty(iterator_to_array($recipe->getFiles()));
        }
    }

    private function createKitFactory(): KitFactory
    {
        return new KitFactory(
            self::getContainer()->get('filesystem'),
            self::getContainer()->get('ux_toolkit.kit.kit_synchronizer')
        );
    }
}
