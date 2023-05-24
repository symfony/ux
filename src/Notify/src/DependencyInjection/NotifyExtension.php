<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Notify\DependencyInjection;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\UX\Notify\Twig\NotifyExtension as TwigNotifyExtension;
use Symfony\UX\Notify\Twig\NotifyRuntime;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class NotifyExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function loadInternal(array $config, ContainerBuilder $container)
    {
        $container->register('notify.twig_extension', TwigNotifyExtension::class)
            ->addTag('twig.extension')
        ;

        $container->register('notify.twig_runtime', NotifyRuntime::class)
            ->setArguments([
                new Reference($config['mercure_hub']),
                new Reference('stimulus.helper'),
            ])
            ->addTag('twig.runtime')
        ;
    }

    public function prepend(ContainerBuilder $container)
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__.'/../../assets/dist' => '@symfony/ux-notify',
                ],
            ],
        ]);
    }
}
