<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\DependencyInjection;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Compiler\AssetCompilerPathResolverTrait;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class StimulusExtension extends Extension implements PrependExtensionInterface, ConfigurationInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->findDefinition('stimulus.asset_mapper.controllers_map_generator')
            ->replaceArgument(2, $config['controller_paths'])
            ->replaceArgument(3, $config['controllers_json']);

        // on older versions, the presence of this service if the trait doesn't exist causes an error
        if (!trait_exists(AssetCompilerPathResolverTrait::class)) {
            $container->removeDefinition('stimulus.asset_mapper.loader_javascript_compiler');
        }
    }

    public function prepend(ContainerBuilder $container)
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__.'/../../assets/dist' => '@symfony/stimulus-bundle',
                ],
                'excluded_patterns' => [
                    '*.d.ts',
                    '**/controllers.json',
                ],
            ],
        ]);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return $this;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('stimulus');
        $rootNode = $treeBuilder->getRootNode();
        \assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode
            ->children()
                ->arrayNode('controller_paths')
                    ->defaultValue(['%kernel.project_dir%/assets/controllers'])
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('controllers_json')
                    ->defaultValue('%kernel.project_dir%/assets/controllers.json')
                ->end()
            ->end();

        return $treeBuilder;
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
