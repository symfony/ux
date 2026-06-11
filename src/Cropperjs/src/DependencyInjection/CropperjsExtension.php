<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Cropperjs\DependencyInjection;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Vips\Driver as VipsDriver;
use Intervention\Image\ImageManager;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\Cropperjs\Factory\Cropper;
use Symfony\UX\Cropperjs\Factory\CropperInterface;
use Symfony\UX\Cropperjs\Form\CropperType;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class CropperjsExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container
            ->setDefinition('form.cropper', new Definition(CropperType::class))
            ->addTag('form.type')
            ->setPublic(false)
        ;

        $container
            ->setDefinition('cropper.image_manager', new Definition(ImageManager::class))
            ->setFactory([ImageManager::class, 'usingDriver'])
            ->setArguments([$this->resolveDriver($config)])
            ->setPublic(false)
        ;

        $container
            ->setDefinition('cropper', new Definition(Cropper::class))
            ->addArgument(new Reference('cropper.image_manager'))
            ->setPublic(false)
        ;

        $container->setAlias(CropperInterface::class, 'cropper')->setPublic(false);
    }

    /**
     * @param array{driver: string, driver_service: string|null} $config
     */
    private function resolveDriver(array $config): Reference|string
    {
        if (null !== $config['driver_service']) {
            return new Reference($config['driver_service']);
        }

        if ('vips' === $config['driver'] && !class_exists(VipsDriver::class)) {
            throw new \LogicException('The "vips" cropperjs driver requires the "intervention/image-driver-vips" package. Try running "composer require intervention/image-driver-vips".');
        }

        return match ($config['driver']) {
            'gd' => GdDriver::class,
            'imagick' => ImagickDriver::class,
            'vips' => VipsDriver::class,
        };
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__.'/../../assets/dist' => '@symfony/ux-cropperjs',
                ],
            ],
        ]);
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
