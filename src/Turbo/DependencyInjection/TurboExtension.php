<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\Turbo\Streams\MercureStreamAdapter;
use Symfony\UX\Turbo\Twig\TurboFrameExtension;
use Symfony\UX\Turbo\Twig\TurboStreamExtension;
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Environment;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class TurboExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Register Turbo Streams config
        if (!empty($config['streams']['adapter'])) {
            $this->registerStreamsConfig($config, $container);
        }

        // Add Twig extension
        if (class_exists(Environment::class)) {
            $container
                ->setDefinition('turbo.twig.frame_extension', new Definition(TurboFrameExtension::class))
                ->addTag('twig.extension')
                ->setPublic(false)
            ;

            if (!empty($config['streams']['adapter']) && class_exists(StimulusTwigExtension::class)) {
                $container
                    ->setDefinition('turbo.twig.stream_extension', new Definition(TurboStreamExtension::class))
                    ->addArgument(new Reference('webpack_encore.twig_stimulus_extension'))
                    ->addArgument(new Reference('turbo.streams.adapter'))
                    ->addArgument($config['streams']['options'] ?? [])
                    ->addTag('twig.extension')
                    ->setPublic(false)
                ;
            }
        }
    }

    private function registerStreamsConfig(array $config, ContainerBuilder $container)
    {
        // Register native stream adapters
        $container->setDefinition('turbo.streams.adapter.mercure', new Definition(MercureStreamAdapter::class))
            ->setPublic(false);

        // Wire configured adapter
        $container->setAlias('turbo.streams.adapter', $config['streams']['adapter'])->setPublic(false);
    }
}
