<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\DependencyInjection;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\StimulusBundle\AssetMapper\ControllersMapGenerator;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class StimulusExtension extends Extension implements PrependExtensionInterface, ConfigurationInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $config = $this->processConfiguration($this, $configs);

        $container->findDefinition('stimulus.asset_mapper.controllers_map_generator')
            ->replaceArgument(2, $config['controller_paths'])
            ->replaceArgument(3, $config['controllers_json']);

        $applications = [];
        $loaderRealpaths = [];
        foreach ($config['applications'] as $name => $application) {
            $loader = $container->getParameterBag()->resolveValue($application['loader']);
            if (!str_ends_with($loader, '.js')) {
                throw new InvalidArgumentException(\sprintf('The loader "%s" of the "%s" Stimulus application must be a ".js" file.', $loader, $name));
            }

            if (!is_file($loader)) {
                throw new InvalidArgumentException(\sprintf('The loader file "%s" of the "%s" Stimulus application does not exist. Create it as an empty file inside an AssetMapper path.', $loader, $name));
            }

            $loaderRealpath = realpath($loader);
            if (isset($loaderRealpaths[$loaderRealpath])) {
                throw new InvalidArgumentException(\sprintf('The loader "%s" is used by both the "%s" and "%s" Stimulus applications.', $loaderRealpath, $loaderRealpaths[$loaderRealpath], $name));
            }
            $loaderRealpaths[$loaderRealpath] = $name;

            $controllersJson = $config['controllers_json'];
            if (null !== $application['controllers_json']) {
                $controllersJson = $container->getParameterBag()->resolveValue($application['controllers_json']);
                if (!is_file($controllersJson)) {
                    throw new InvalidArgumentException(\sprintf('The controllers.json file "%s" of the "%s" Stimulus application does not exist.', $controllersJson, $name));
                }
            }

            $generatorId = 'stimulus.asset_mapper.controllers_map_generator.'.$name;
            $container->register($generatorId, ControllersMapGenerator::class)
                ->setArguments([
                    new Reference('asset_mapper'),
                    new Reference('stimulus.asset_mapper.ux_package_reader'),
                    $application['include_global_paths'] ? $config['controller_paths'] : [],
                    $controllersJson,
                    new Reference('stimulus.asset_mapper.auto_import_locator', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                    $application['controller_paths'],
                    $application['merge_controllers_json'] ? $config['controllers_json'] : null,
                ]);

            $applications[$name] = [
                'loader' => $loader,
                'generator' => new Reference($generatorId),
            ];
        }

        $container->findDefinition('stimulus.asset_mapper.loader_javascript_compiler')
            ->setArgument(2, $applications);

        if (!class_exists(ImportMapConfigReader::class)) {
            $container->removeDefinition('stimulus.asset_mapper.auto_import_locator');
        }
    }

    public function prepend(ContainerBuilder $container): void
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
                    '*/controllers.json',
                ],
            ],
        ]);
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
                ->arrayNode('applications')
                    ->useAttributeAsKey('name')
                    ->normalizeKeys(false)
                    ->validate()
                        ->always(static function (array $applications): array {
                            foreach ($applications as $name => $application) {
                                $name = (string) $name;
                                if ('default' === $name) {
                                    throw new \InvalidArgumentException('The Stimulus application name "default" is reserved.');
                                }

                                if (!preg_match('/^[a-z][a-z0-9_-]*$/', $name)) {
                                    throw new \InvalidArgumentException(\sprintf('Invalid Stimulus application name "%s": it must start with a lowercase letter and contain only lowercase letters, digits, "_" and "-".', $name));
                                }
                            }

                            return $applications;
                        })
                    ->end()
                    ->arrayPrototype()
                        ->validate()
                            ->ifTrue(static fn (array $application): bool => $application['merge_controllers_json'] && null === $application['controllers_json'])
                            ->thenInvalid('The "merge_controllers_json" option requires the "controllers_json" option.')
                        ->end()
                        ->children()
                            ->scalarNode('loader')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('controller_paths')
                                ->defaultValue([])
                                ->scalarPrototype()->end()
                            ->end()
                            ->booleanNode('include_global_paths')
                                ->defaultTrue()
                            ->end()
                            ->scalarNode('controllers_json')
                                ->defaultNull()
                                ->validate()
                                    ->ifTrue(static fn (mixed $controllersJson): bool => '' === $controllersJson)
                                    ->thenInvalid('The "controllers_json" option cannot be an empty string. Omit it or set it to null to use the global file.')
                                ->end()
                            ->end()
                            ->booleanNode('merge_controllers_json')
                                ->defaultFalse()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
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
