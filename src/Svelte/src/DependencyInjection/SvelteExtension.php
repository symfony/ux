<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Svelte\DependencyInjection;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Compiler\AssetCompilerPathResolverTrait;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\Svelte\AssetMapper\SvelteControllerLoaderAssetCompiler;
use Symfony\UX\Svelte\Twig\SvelteComponentExtension;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Thomas Choquet <thomas.choquet.pro@gmail.com>
 *
 * @internal
 */
class SvelteExtension extends Extension implements PrependExtensionInterface, ConfigurationInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container
            ->setDefinition('twig.extension.svelte', new Definition(SvelteComponentExtension::class))
            ->setArgument(0, new Reference('stimulus.helper'))
            ->addTag('twig.extension')
            ->setPublic(false)
        ;

        // on older versions, the absence of this trait will trigger an error if the service is loaded
        if (trait_exists(AssetCompilerPathResolverTrait::class)) {
            $container->setDefinition('svelte.asset_mapper.svelte_controller_loader_compiler', new Definition(SvelteControllerLoaderAssetCompiler::class))
                ->setArguments([
                    $config['controllers_path'],
                    $config['name_glob'],
                ])
                // run before the core JavaScript compiler
                ->addTag('asset_mapper.compiler', ['priority' => 100]);
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
                    __DIR__.'/../../assets/dist' => '@symfony/ux-svelte',
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
        $treeBuilder = new TreeBuilder('svelte');
        $rootNode = $treeBuilder->getRootNode();
        \assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode
            ->children()
                ->scalarNode('controllers_path')
                    ->info('The path to the directory where Svelte controller components are stored - relevant only when using symfony/asset-mapper.')
                    ->defaultValue('%kernel.project_dir%/assets/svelte/controllers')
                ->end()
                ->arrayNode('name_glob')
                    ->info('The glob patterns to use to find Svelte controller components inside of controllers_path')
                    // find .js (already compiled) or .svelte, in case the user will have an asset compiler to do the .svelte -> .js compilation
                    ->defaultValue(['*.js', '*.svelte'])
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
