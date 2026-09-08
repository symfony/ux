<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Cropperjs\Tests\DependencyInjection;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Vips\Driver as VipsDriver;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\Cropperjs\DependencyInjection\CropperjsExtension;
use Symfony\UX\Cropperjs\Factory\CropperInterface;
use Symfony\UX\Cropperjs\Intervention\InterventionImage;

/**
 * @internal
 */
class CropperjsExtensionTest extends TestCase
{
    private function loadContainer(array $config = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $extension = new CropperjsExtension();
        $extension->load([$config], $container);

        return $container;
    }

    private function skipUnlessDriverClasses(): void
    {
        if (!InterventionImage::supportsDriverClasses()) {
            $this->markTestSkipped('Driver classes require intervention/image 3.0 or higher.');
        }
    }

    private function skipUnlessLegacy(): void
    {
        if (InterventionImage::V2 !== InterventionImage::major()) {
            $this->markTestSkipped('This behavior is specific to intervention/image 2.');
        }
    }

    public function testImageManagerUsesGdDriverByDefault(): void
    {
        $this->skipUnlessDriverClasses();

        $definition = $this->loadContainer()->getDefinition('cropper.image_manager');

        $factoryMethod = InterventionImage::V4 === InterventionImage::major() ? 'usingDriver' : 'withDriver';
        $this->assertSame([ImageManager::class, $factoryMethod], $definition->getFactory());
        $this->assertSame([GdDriver::class], $definition->getArguments());
    }

    public function testImageManagerUsesImagickDriver(): void
    {
        $this->skipUnlessDriverClasses();

        $definition = $this->loadContainer(['driver' => 'imagick'])->getDefinition('cropper.image_manager');

        $this->assertSame([ImagickDriver::class], $definition->getArguments());
    }

    public function testCustomDriverServiceTakesPrecedenceOverDriver(): void
    {
        $this->skipUnlessDriverClasses();

        $definition = $this->loadContainer([
            'driver' => 'imagick',
            'driver_service' => 'app.custom_driver',
        ])->getDefinition('cropper.image_manager');

        $arguments = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertSame('app.custom_driver', (string) $arguments[0]);
    }

    public function testVipsDriverThrowsWhenDriverPackageNotInstalled(): void
    {
        $this->skipUnlessDriverClasses();

        if (class_exists(VipsDriver::class)) {
            $this->markTestSkipped('intervention/image-driver-vips is installed; cannot test the missing-package guard.');
        }

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('intervention/image-driver-vips');

        $this->loadContainer(['driver' => 'vips']);
    }

    public function testLegacyImageManagerIsBuiltFromTheDriverName(): void
    {
        $this->skipUnlessLegacy();

        $definition = $this->loadContainer(['driver' => 'imagick'])->getDefinition('cropper.image_manager');

        $this->assertNull($definition->getFactory());
        $this->assertSame([['driver' => 'imagick']], $definition->getArguments());
    }

    public function testLegacyRejectsTheVipsDriver(): void
    {
        $this->skipUnlessLegacy();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('3.0 or higher');

        $this->loadContainer(['driver' => 'vips']);
    }

    public function testLegacyRejectsACustomDriverService(): void
    {
        $this->skipUnlessLegacy();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('3.0 or higher');

        $this->loadContainer(['driver_service' => 'app.custom_driver']);
    }

    public function testConfiguredImageManagerCanBeInstantiated(): void
    {
        $container = $this->loadContainer();
        $container->getDefinition('cropper.image_manager')->setPublic(true);
        $container->getAlias(CropperInterface::class)->setPublic(true);
        $container->compile();

        $this->assertInstanceOf(ImageManager::class, $container->get('cropper.image_manager'));
        $this->assertInstanceOf(CropperInterface::class, $container->get(CropperInterface::class));
    }
}
