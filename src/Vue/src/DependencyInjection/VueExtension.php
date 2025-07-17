<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Vue\DependencyInjection;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\Vue\AssetMapper\VueControllerLoaderAssetCompiler;
use Symfony\UX\Vue\Twig\VueComponentExtension;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Thibault RICHARD <thibault.richard62@gmail.com>
 *
 * @internal
 */
class VueExtension extends Extension implements PrependExtensionInterface, ConfigurationInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container
            ->setDefinition('twig.extension.vue', new Definition(VueComponentExtension::class))
            ->setArgument(0, new Reference('stimulus.helper'))
            ->addTag('twig.extension')
            ->setPublic(false)
        ;

        $container->setDefinition('vue.asset_mapper.vue_controller_loader_compiler', new Definition(VueControllerLoaderAssetCompiler::class))
            ->setArguments([
                $config['controllers_path'],
                $config['name_glob'],
            ])
            // run before the core JavaScript compiler
            ->addTag('asset_mapper.compiler', ['priority' => 100])
        ;
    }

    public function prepend(ContainerBuilder $container)
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__.'/../../assets/dist' => '@symfony/ux-vue',
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
        $treeBuilder = new TreeBuilder('vue');
        $rootNode = $treeBuilder->getRootNode();
        \assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode
            ->children()
                ->scalarNode('controllers_path')
                    ->info('The path to the directory where Vue controller components are stored - relevant only when using symfony/asset-mapper.')
                    ->defaultValue('%kernel.project_dir%/assets/vue/controllers')
                ->end()
                ->arrayNode('name_glob')
                    ->info('The glob patterns to use to find Vue controller components inside of controllers_path')
                    // find .js (.vue SFC are not supported) - they must be written or compiled to .js
                    ->defaultValue(['*.js'])
                    ->scalarPrototype()->end()
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
