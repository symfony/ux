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
use Intervention\Image\Interfaces\ImageManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\Cropperjs\DependencyInjection\CropperjsExtension;
use Symfony\UX\Cropperjs\Factory\CropperInterface;

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

    public function testImageManagerUsesGdDriverByDefault()
    {
        $definition = $this->loadContainer()->getDefinition('cropper.image_manager');

        $this->assertSame([ImageManager::class, 'usingDriver'], $definition->getFactory());
        $this->assertSame([GdDriver::class], $definition->getArguments());
    }

    public function testImageManagerUsesImagickDriver()
    {
        $definition = $this->loadContainer(['driver' => 'imagick'])->getDefinition('cropper.image_manager');

        $this->assertSame([ImagickDriver::class], $definition->getArguments());
    }

    public function testCustomDriverServiceTakesPrecedenceOverDriver()
    {
        $definition = $this->loadContainer([
            'driver' => 'imagick',
            'driver_service' => 'app.custom_driver',
        ])->getDefinition('cropper.image_manager');

        $arguments = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertSame('app.custom_driver', (string) $arguments[0]);
    }

    public function testVipsDriverThrowsWhenDriverPackageNotInstalled()
    {
        if (class_exists(VipsDriver::class)) {
            $this->markTestSkipped('intervention/image-driver-vips is installed; cannot test the missing-package guard.');
        }

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('intervention/image-driver-vips');

        $this->loadContainer(['driver' => 'vips']);
    }

    public function testConfiguredImageManagerCanBeInstantiated()
    {
        $container = $this->loadContainer();
        $container->getDefinition('cropper.image_manager')->setPublic(true);
        $container->getAlias(CropperInterface::class)->setPublic(true);
        $container->compile();

        $this->assertInstanceOf(ImageManagerInterface::class, $container->get('cropper.image_manager'));
        $this->assertInstanceOf(CropperInterface::class, $container->get(CropperInterface::class));
    }
}
