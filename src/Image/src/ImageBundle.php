<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * @author Sébastien Jean <sebastien.jean76@gmail.com>
 */
final class ImageBundle extends AbstractBundle
{
    protected string $extensionAlias = 'ux_image';

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        if (ContainerBuilder::willBeAvailable('symfony/ux-twig-component', TwigComponentBundle::class, ['symfony/ux-image'])) {
            $container->import('../config/twig_component.php');
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('twig', [
            'paths' => [
                __DIR__.'/../templates' => 'UXImage',
            ],
        ]);
    }
}
