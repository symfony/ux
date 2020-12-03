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

use Intervention\Image\ImageManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\Cropperjs\Factory\Cropper;
use Symfony\UX\Cropperjs\Factory\CropperInterface;
use Symfony\UX\Cropperjs\Form\CropperType;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class CropperjsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $container
            ->setDefinition('form.cropper', new Definition(CropperType::class))
            ->addTag('form.type')
            ->setPublic(false)
        ;

        $container
            ->setDefinition('cropper.image_manager', new Definition(ImageManager::class))
            ->setPublic(false)
        ;

        $container
            ->setDefinition('cropper', new Definition(Cropper::class))
            ->addArgument(new Reference('cropper.image_manager'))
            ->setPublic(false)
        ;

        $container->setAlias(CropperInterface::class, 'cropper')->setPublic(false);
    }
}
