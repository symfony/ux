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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\UX\Notify\Twig\NotifyExtension as TwigNotifyExtension;
use Symfony\UX\Notify\Twig\NotifyRuntime;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class NotifyExtension extends ConfigurableExtension
{
    /**
     * {@inheritdoc}
     */
    public function loadInternal(array $config, ContainerBuilder $container)
    {
        $container->register('notify.twig_extension', TwigNotifyExtension::class)
            ->addTag('twig.extension')
        ;

        $container->register('notify.twig_runtime', NotifyRuntime::class)
            ->setArguments([
                new Reference($config['mercure_hub']),
                new Reference('webpack_encore.twig_stimulus_extension'),
            ])
            ->addTag('twig.runtime')
        ;
    }
}
