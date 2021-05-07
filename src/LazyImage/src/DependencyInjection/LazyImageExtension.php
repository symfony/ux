<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LazyImage\DependencyInjection;

use Intervention\Image\ImageManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\LazyImage\BlurHash\BlurHash;
use Symfony\UX\LazyImage\BlurHash\BlurHashInterface;
use Symfony\UX\LazyImage\Twig\BlurHashExtension;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class LazyImageExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        if (class_exists(ImageManager::class)) {
            $container
                ->setDefinition('lazy_image.image_manager', new Definition(ImageManager::class))
                ->setPublic(false)
            ;
        }

        $container
            ->setDefinition('lazy_image.blur_hash', new Definition(BlurHash::class))
            ->addArgument(new Reference('lazy_image.image_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->setPublic(false)
        ;

        $container->setAlias(BlurHashInterface::class, 'lazy_image.blur_hash')->setPublic(false);

        $container
            ->setDefinition('twig.extension.blur_hash', new Definition(BlurHashExtension::class))
            ->addArgument(new Reference('lazy_image.blur_hash'))
            ->addTag('twig.extension')
            ->setPublic(false)
        ;
    }
}
