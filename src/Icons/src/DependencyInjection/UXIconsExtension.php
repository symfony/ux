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

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\UX\Icons\IconFinderInterface;
use Symfony\UX\Icons\Iconify;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class UXIconsExtension extends ConfigurableExtension implements ConfigurationInterface, PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        // The dist path must be mapped even without the "javascript" option: Flex requires the package's importmap entries on install
        $paths = [__DIR__.'/../../assets/dist' => '@symfony/ux-icons'];

        $config = $this->processConfiguration($this, $container->getExtensionConfig($this->getAlias()));
        if ($config['javascript']['enabled']) {
            $paths[$config['javascript']['dump_directory']] = 'var/icons';
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => $paths,
            ],
        ]);
    }

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
                ->arrayNode('default_icon_attributes')
                    ->info('Default attributes to add to all icons.')
                    ->defaultValue(['fill' => 'currentColor'])
                    ->example(['class' => 'icon'])
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('key')
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->end()
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
                                ->useAttributeAsKey('key')
                                ->scalarPrototype()
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                            ->arrayNode('suffixes')
                                ->info("Suffix-based attributes (following Iconify naming conventions).\nhttps://iconify.design/docs/libraries/tools/icon-set/themes.html")
                                ->normalizeKeys(false)
                                ->useAttributeAsKey('suffix')
                                ->arrayPrototype()
                                    ->info('The suffix name (e.g. "solid", "20-solid")')
                                    ->children()
                                        ->arrayNode('icon_attributes')
                                            ->info('Attributes for icons matching this suffix.')
                                            ->normalizeKeys(false)
                                            ->useAttributeAsKey('key')
                                            ->scalarPrototype()
                                                ->cannotBeEmpty()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(static fn (array $v) => isset($v['path']) && isset($v['alias']))
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
                    ->useAttributeAsKey('key')
                    ->{method_exists(ArrayNodeDefinition::class, 'stringPrototype') ? 'stringPrototype' : 'scalarPrototype'}()
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
                        ->booleanNode('auto_lock')
                            ->info("Persist \"on demand\" icons to the local icon directory (see \"icon_dir\").\nRecommended in dev only. Requires \"on_demand\" to be enabled.")
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('endpoint')
                            ->info('The endpoint for the Iconify icons API.')
                            ->defaultValue(Iconify::API_ENDPOINT)
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('javascript')
                    ->info('Dump local icons as a JavaScript module, to render them with "@symfony/ux-icons".')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('dump_directory')
                            ->info('The directory where the JavaScript module is dumped.')
                            ->defaultValue('%kernel.project_dir%/var/icons')
                            ->cannotBeEmpty()
                        ->end()
                        ->booleanNode('dump_typescript')
                            ->info('Whether TypeScript types are dumped alongside the icons. Disable this if you do not use TypeScript (e.g. in production when using AssetMapper).')
                            ->defaultTrue()
                        ->end()
                        ->arrayNode('scan_directories')
                            ->info('The directories scanned for icons imported in JavaScript files (see "ux:icons:lock").')
                            ->defaultValue(['%kernel.project_dir%/assets'])
                            ->scalarPrototype()->end()
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
        $iconSuffixAttributes = [];
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
            if (isset($config['suffixes'])) {
                $suffixes = array_map(static fn ($s) => $s['icon_attributes'] ?? [], $config['suffixes']);
                uksort($suffixes, static fn ($a, $b) => \strlen($b) <=> \strlen($a));
                $iconSuffixAttributes[$prefix] = $suffixes;
            }
        }

        $container->registerForAutoconfiguration(IconFinderInterface::class)
            ->addTag('ux_icons.finder');

        $container->getDefinition('.ux_icons.local_svg_icon_registry')
            ->setArgument(1, $mergedConfig['icon_dir'])
            ->setArgument(2, $iconSetPaths)
        ;

        $container->getDefinition('.ux_icons.template_icon_finder')
            ->setArgument(1, $mergedConfig['icon_dir'])
        ;

        $container->getDefinition('.ux_icons.icon_renderer')
            ->setArgument(1, $mergedConfig['default_icon_attributes'])
            ->setArgument(2, $mergedConfig['aliases'])
            ->setArgument(3, $iconSetAttributes)
            ->setArgument(4, $iconSuffixAttributes)
        ;

        if ($mergedConfig['javascript']['enabled']) {
            $loader->load('javascript.php');

            $container->getDefinition('.ux_icons.javascript.icon_renderer')
                ->setArgument(1, $mergedConfig['default_icon_attributes'])
                ->setArgument(2, $mergedConfig['aliases'])
                ->setArgument(3, $iconSetAttributes)
                ->setArgument(4, $iconSuffixAttributes)
            ;

            $container->getDefinition('.ux_icons.javascript.dumper')
                ->setArgument(1, $mergedConfig['icon_dir'])
                ->setArgument(2, $iconSetPaths)
                ->setArgument(3, $mergedConfig['aliases'])
                ->setArgument(4, $mergedConfig['javascript']['dump_directory'])
                ->setArgument(5, $mergedConfig['javascript']['dump_typescript'])
            ;

            $container->getDefinition('.ux_icons.command.import')
                ->setArgument(2, new Reference('.ux_icons.javascript.dumper'))
            ;

            $container->getDefinition('.ux_icons.javascript.icon_finder')
                ->setArgument(0, $mergedConfig['javascript']['scan_directories'])
            ;
        }

        $container->getDefinition('.ux_icons.twig_icon_runtime')
            ->setArgument(1, $mergedConfig['ignore_not_found'])
        ;

        $container->getDefinition('.ux_icons.iconify')
            ->setArgument(2, $mergedConfig['iconify']['endpoint']);

        $container->getDefinition('.ux_icons.iconify_on_demand_registry')
            ->setArgument(1, $iconSetAliases);

        $container->getDefinition('.ux_icons.command.lock')
            ->setArgument(3, $mergedConfig['aliases'])
            ->setArgument(4, $iconSetAliases);

        if ($mergedConfig['javascript']['enabled']) {
            $container->getDefinition('.ux_icons.command.lock')
                ->setArgument(5, new Reference('.ux_icons.javascript.dumper'));
        }

        if (!$mergedConfig['iconify']['on_demand'] || !$mergedConfig['iconify']['enabled']) {
            $container->removeDefinition('.ux_icons.iconify_on_demand_registry');
            $container->removeDefinition('.ux_icons.auto_lock_icon_registry');
        } elseif ($mergedConfig['iconify']['auto_lock']) {
            $container->getDefinition('.ux_icons.iconify_on_demand_registry')->clearTag('ux_icons.registry');
            $container->getDefinition('.ux_icons.auto_lock_icon_registry')
                ->addTag('ux_icons.registry', ['priority' => -10]);
        } else {
            $container->removeDefinition('.ux_icons.auto_lock_icon_registry');
        }
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // Before Symfony 8.2, FrameworkBundle provided the AssetMapper configuration.
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return isset($bundlesMetadata['AssetMapperBundle'])
            || is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
