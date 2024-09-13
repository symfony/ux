<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\DependencyInjection;

use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Icons\Iconify;
use Symfony\UX\Icons\IconPreRendererInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class UXIconsExtension extends ConfigurableExtension implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('ux_icons');
        $rootNode = $builder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('icon_dir')
                    ->info('The local directory where icons are stored.')
                    ->defaultValue('%kernel.project_dir%/assets/icons')
                ->end()
                ->variableNode('default_icon_attributes')
                    ->info('Default attributes to add to all icons.')
                    ->defaultValue(['fill' => 'currentColor'])
                ->end()
                ->arrayNode('aliases')
                    ->info('Icon aliases (alias => icon name).')
                    ->example(['dots' => 'clarity:ellipsis-horizontal-line'])
                    ->normalizeKeys(false)
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
                ->arrayNode('iconify')
                    ->info('Configuration for the "on demand" icons powered by Iconify.design.')
                    ->{interface_exists(HttpClientInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->children()
                        ->booleanNode('on_demand')
                            ->info('Whether to use the "on demand" icons powered by Iconify.design.')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('endpoint')
                            ->info('The endpoint for the Iconify API.')
                            ->defaultValue(Iconify::API_ENDPOINT)
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('ignore_not_found')
                    ->info('Ignore error when an icon is not found.')
                    ->defaultFalse()
                ->end()
            ->end()
        ;

        return $builder;
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return $this;
    }

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        if (isset($container->getParameter('kernel.bundles')['TwigComponentBundle'])) {
            $loader->load('twig_component.php');
        }

        if (class_exists(PreAssetsCompileEvent::class)) {
            $loader->load('asset_mapper.php');
        }

        $container->getDefinition('.ux_icons.local_svg_icon_registry')
            ->setArguments([
                $mergedConfig['icon_dir'],
            ])
        ;

        $container->getDefinition('.ux_icons.icon_finder')
            ->setArgument(1, $mergedConfig['icon_dir'])
        ;

        $container->getDefinition('.ux_icons.icon_renderer')
            ->setArgument(1, $mergedConfig['default_icon_attributes'])
            ->setArgument(2, $mergedConfig['aliases'])
        ;

        $container->getDefinition('.ux_icons.twig_icon_runtime')
            ->setArgument(1, $mergedConfig['ignore_not_found'])
        ;

        if ($mergedConfig['iconify']['enabled']) {
            $loader->load('iconify.php');

            $container->getDefinition('.ux_icons.iconify')
                ->setArgument(1, $mergedConfig['iconify']['endpoint'])
            ;

            if (!$mergedConfig['iconify']['on_demand']) {
                $container->removeDefinition('.ux_icons.iconify_on_demand_registry');
            }
        }

        if (!$container->getParameter('kernel.debug')) {
            $container->removeDefinition('.ux_icons.command.import');
        }

        $container
            ->registerForAutoconfiguration(IconPreRendererInterface::class)
            ->addTag('ux_icons.icon_pre_renderer');
    }
}
