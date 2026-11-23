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
use Symfony\Component\Config\Resource\FileExistenceResource;
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
            $module = $container->getParameterBag()->resolveValue($application['loader'] ?? '%kernel.project_dir%/assets/'.$name.'/stimulus.js');
            if (!str_ends_with($module, '.js')) {
                throw new InvalidArgumentException(\sprintf('The module "%s" of the "%s" Stimulus application must be a ".js" file.', $module, $name));
            }

            if (!is_file($module)) {
                throw new InvalidArgumentException(\sprintf('The module "%s" of the "%s" Stimulus application does not exist. Create it inside an AssetMapper path, either empty or containing only "export { startStimulusApp } from \'@symfony/stimulus-bundle\';".', $module, $name));
            }

            $moduleRealpath = realpath($module);
            if (isset($loaderRealpaths[$moduleRealpath])) {
                throw new InvalidArgumentException(\sprintf('The module "%s" is used by both the "%s" and "%s" Stimulus applications.', $moduleRealpath, $loaderRealpaths[$moduleRealpath], $name));
            }
            $loaderRealpaths[$moduleRealpath] = $name;

            $controllerPaths = $application['controller_paths'];
            $watchedPaths = [];
            if (null === $controllerPaths) {
                $conventionPath = '%kernel.project_dir%/assets/'.$name.'/controllers';
                $resolvedConventionPath = $container->getParameterBag()->resolveValue($conventionPath);
                // Rebuild the container when the directory appears or disappears.
                $container->addResource(new FileExistenceResource($resolvedConventionPath));
                $controllerPaths = is_dir($resolvedConventionPath) ? [$conventionPath] : [];
                // A missing directory cannot be an asset dependency: watch its parent, so the compiled module is refreshed once it appears.
                if ([] === $controllerPaths && is_dir(\dirname($resolvedConventionPath))) {
                    $watchedPaths[] = \dirname($resolvedConventionPath);
                }
            }

            // Without the global files, an application gets no UX controller unless it declares its own controllers.json.
            $controllersJson = $application['include_global'] ? $config['controllers_json'] : null;
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
                    $application['include_global'] ? $config['controller_paths'] : [],
                    $controllersJson,
                    new Reference('stimulus.asset_mapper.auto_import_locator', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                    $controllerPaths,
                    $application['merge_controllers_json'] ? $config['controllers_json'] : null,
                ]);

            $applications[$name] = [
                'loader' => $module,
                'generator' => new Reference($generatorId),
                'watched_paths' => $watchedPaths,
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
                            foreach (array_keys($applications) as $name) {
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
                        ->validate()
                            ->ifTrue(static fn (array $application): bool => $application['merge_controllers_json'] && !$application['include_global'])
                            ->thenInvalid('The "merge_controllers_json" option cannot be used with "include_global: false": there is no global controllers.json file to merge over.')
                        ->end()
                        ->children()
                            ->scalarNode('loader')
                                ->info('The module of the application, compiled by the bundle. Defaults to "%kernel.project_dir%/assets/<name>/stimulus.js".')
                                ->defaultNull()
                                ->validate()
                                    ->ifTrue(static fn (mixed $loader): bool => '' === $loader)
                                    ->thenInvalid('The "loader" option cannot be an empty string. Omit it to use "assets/<name>/stimulus.js".')
                                ->end()
                            ->end()
                            ->arrayNode('controller_paths')
                                ->info('Defaults to "%kernel.project_dir%/assets/<name>/controllers" when that directory exists.')
                                ->defaultNull()
                                ->scalarPrototype()->end()
                            ->end()
                            ->booleanNode('include_global')
                                ->info('Also load the global controller_paths and controllers_json. When false, the application only gets its own controllers.')
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
