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
use Symfony\UX\Icons\Iconify;

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
                    ->example(['class' => 'icon'])
                ->end()
                ->arrayNode('icon_sets')
                    ->info('Icon sets configuration.')
                    ->defaultValue([])
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('prefix')
                    ->arrayPrototype()
                        ->info('the icon set prefix (e.g. "acme")')
                        ->children()
                            ->scalarNode('path')
                                ->info("The local icon set directory path.\n(cannot be used with 'alias')")
                                ->example('%kernel.project_dir%/assets/svg/acme')
                            ->end()
                            ->scalarNode('alias')
                                ->info("The remote icon set identifier.\n(cannot be used with 'path')")
                                ->example('simple-icons')
                            ->end()
                            ->arrayNode('icon_attributes')
                                ->info('Override default icon attributes for icons in this set.')
                                ->example(['class' => 'icon icon-acme', 'fill' => 'none'])
                                ->normalizeKeys(false)
                                ->variablePrototype()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(fn (array $v) => isset($v['path']) && isset($v['alias']))
                        ->thenInvalid('You cannot define both "path" and "alias" for an icon set.')
                    ->end()
                ->end()
                ->arrayNode('aliases')
                    ->info('Icon aliases (map of alias => full name).')
                    ->example([
                        'dots' => 'clarity:ellipsis-horizontal-line',
                        'privacy' => 'bi:cookie',
                    ])
                    ->normalizeKeys(false)
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
                ->arrayNode('iconify')
                    ->info('Configuration for the remote icon service.')
                    ->canBeDisabled()
                    ->children()
                        ->booleanNode('on_demand')
                            ->info('Whether to download icons "on demand".')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('endpoint')
                            ->info('The endpoint for the Iconify icons API.')
                            ->defaultValue(Iconify::API_ENDPOINT)
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('ignore_not_found')
                    ->info("Ignore error when an icon is not found.\nSet to 'true' to fail silently.")
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

        $iconSetAliases = [];
        $iconSetAttributes = [];
        $iconSetPaths = [];
        foreach ($mergedConfig['icon_sets'] as $prefix => $config) {
            if (isset($config['icon_attributes'])) {
                $iconSetAttributes[$prefix] = $config['icon_attributes'];
            }
            if (isset($config['alias'])) {
                $iconSetAliases[$prefix] = $config['alias'];
            }
            if (isset($config['path'])) {
                $iconSetPaths[$prefix] = $config['path'];
            }
        }

        $container->getDefinition('.ux_icons.local_svg_icon_registry')
            ->setArguments([
                $mergedConfig['icon_dir'],
                $iconSetPaths,
            ])
        ;

        $container->getDefinition('.ux_icons.icon_finder')
            ->setArgument(1, $mergedConfig['icon_dir'])
        ;

        $container->getDefinition('.ux_icons.icon_renderer')
            ->setArgument(1, $mergedConfig['default_icon_attributes'])
            ->setArgument(2, $mergedConfig['aliases'])
            ->setArgument(3, $iconSetAttributes)
        ;

        $container->getDefinition('.ux_icons.twig_icon_runtime')
            ->setArgument(1, $mergedConfig['ignore_not_found'])
        ;

        $container->getDefinition('.ux_icons.iconify')
            ->setArgument(1, $mergedConfig['iconify']['endpoint']);

        $container->getDefinition('.ux_icons.iconify_on_demand_registry')
            ->setArgument(1, $iconSetAliases);

        $container->getDefinition('.ux_icons.command.lock')
            ->setArgument(3, $mergedConfig['aliases'])
            ->setArgument(4, $iconSetAliases);

        if (!$mergedConfig['iconify']['on_demand'] || !$mergedConfig['iconify']['enabled']) {
            $container->removeDefinition('.ux_icons.iconify_on_demand_registry');
        }

        if (!$container->getParameter('kernel.debug')) {
            $container->removeDefinition('.ux_icons.command.import');
            $container->removeDefinition('.ux_icons.command.search');
            $container->removeDefinition('.ux_icons.command.lock');
        }
    }
}
