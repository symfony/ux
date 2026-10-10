<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\DesignTokens\CacheWarmer\StylesheetCache;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Exception\RuntimeException;
use Symfony\UX\DesignTokens\Generator\GeneratorInterface;
use Symfony\UX\DesignTokens\Importer\ImporterInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class UXDesignTokensBundle extends AbstractBundle
{
    public const ASSET_NAMESPACE = 'design-tokens';

    protected string $extensionAlias = 'ux_design_tokens';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('paths')
                    ->info('DTCG token files, merged in order.')
                    ->scalarPrototype()->cannotBeEmpty()->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('resolver')
                    ->info('A DTCG 2025.10 Resolver document and the contexts it selects by default.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('path')
                            ->defaultNull()
                            ->cannotBeEmpty()
                            ->info('Path to a .resolver.json document.')
                        ->end()
                        ->arrayNode('inputs')
                            ->info('Context selected for each modifier, such as {scheme: dark}.')
                            ->normalizeKeys(false)
                            ->variablePrototype()
                                ->validate()
                                    ->ifTrue(static fn (mixed $value): bool => !\is_string($value) && !\is_int($value) && !\is_float($value))
                                    ->thenInvalid('Resolver input values must be strings or numbers.')
                                ->end()
                            ->end()
                            ->defaultValue([])
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(static fn (array $resolver): bool => [] !== $resolver['inputs'] && null === $resolver['path'])
                        ->thenInvalid('"resolver.inputs" requires "resolver.path": inputs select contexts of a Resolver document.')
                    ->end()
                ->end()
                ->arrayNode('color_scheme')
                    ->info('The Resolver modifier whose contexts the CSS output writes as the light and dark color schemes.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('modifier')
                            ->defaultValue('scheme')
                            ->cannotBeEmpty()
                            ->info('Name of the modifier.')
                        ->end()
                        ->scalarNode('light')
                            ->defaultValue('light')
                            ->cannotBeEmpty()
                            ->info('Context written to :root.')
                        ->end()
                        ->scalarNode('dark')
                            ->defaultValue('dark')
                            ->cannotBeEmpty()
                            ->info('Context whose differences are written for prefers-color-scheme: dark and [data-theme="dark"].')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('css_prefix')
                    ->defaultValue('dt')
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(static fn (mixed $value): bool => !\is_string($value) || !preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $value))
                        ->thenInvalid('The CSS prefix must start with a letter or underscore and contain only letters, digits, underscores, or hyphens.')
                    ->end()
                    ->info('Application-owned prefix for generated CSS custom properties.')
                ->end()
            ->end()
        ;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $projectDir = $builder->getParameter('kernel.project_dir');
        \assert(\is_string($projectDir));

        $pathsConfig = $config['paths'] ?? [];
        \assert(\is_array($pathsConfig));
        /** @var list<string> $pathsConfig */
        $paths = array_map(
            fn (string $path): string => $this->resolveProjectPath($path, $projectDir, $builder),
            $pathsConfig,
        );
        $resolver = $config['resolver'];
        \assert(\is_array($resolver));
        $colorScheme = $config['color_scheme'];
        \assert(\is_array($colorScheme));
        $resolverPath = \is_string($resolver['path'] ?? null)
            ? $this->resolveProjectPath($resolver['path'], $projectDir, $builder)
            : null;

        $container->parameters()
            ->set('.ux_design_tokens.paths', $paths)
            ->set('.ux_design_tokens.resolver_path', $resolverPath)
            ->set('.ux_design_tokens.allowed_roots', $this->allowedRoots($paths, $resolverPath, $projectDir))
            ->set('.ux_design_tokens.resolver_inputs', $resolver['inputs'])
            ->set('.ux_design_tokens.color_scheme.modifier', $colorScheme['modifier'])
            ->set('.ux_design_tokens.color_scheme.light', $colorScheme['light'])
            ->set('.ux_design_tokens.color_scheme.dark', $colorScheme['dark'])
            ->set('.ux_design_tokens.css_prefix', $config['css_prefix'])
        ;

        // A path built from an environment variable is unknown until runtime.
        foreach ([...$paths, $resolverPath] as $path) {
            if (!\is_string($path) || $this->holdsEnvPlaceholder($path, $builder)) {
                continue;
            }
            if (is_file($path)) {
                $builder->addResource(new FileResource($path));
            } else {
                $builder->fileExists($path);
            }
        }

        $builder->registerForAutoconfiguration(GeneratorInterface::class)
            ->addTag('ux_design_tokens.generator');

        $builder->registerForAutoconfiguration(ImporterInterface::class)
            ->addTag('ux_design_tokens.importer');

        $container->import('../config/services.php');

        // Invalidates a stylesheet written with another configuration.
        $builder->getDefinition('.ux_design_tokens.stylesheet_cache')->replaceArgument(6, hash('xxh128', serialize([
            $paths,
            $resolverPath,
            $resolver['inputs'],
            $colorScheme,
            $config['css_prefix'],
        ])));

        $bundles = $builder->hasParameter('kernel.bundles') ? $builder->getParameter('kernel.bundles') : [];

        if (\is_array($bundles) && isset($bundles['FrameworkBundle'])) {
            $container->import('../config/cache.php');
        }

        if (\is_array($bundles) && isset($bundles['TwigBundle'])) {
            $container->import('../config/twig.php');
        }

        if ($this->isAssetMapperAvailable($builder)) {
            $container->import('../config/asset_mapper.php');
        }
    }

    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    private function allowedRoots(array $paths, ?string $resolverPath, string $projectDir): array
    {
        $roots = [];
        foreach ([$projectDir, ...array_map('dirname', $paths), null !== $resolverPath ? \dirname($resolverPath) : null] as $directory) {
            if (\is_string($directory) && false !== $real = realpath($directory)) {
                $roots[$real] = true;
            }
        }

        return array_keys($roots);
    }

    private function holdsEnvPlaceholder(string $value, ContainerBuilder $builder): bool
    {
        if (str_contains($value, '%env(')) {
            return true;
        }

        $envs = [];
        $builder->resolveEnvPlaceholders($value, null, $envs);

        return [] !== $envs;
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!$this->isAssetMapperAvailable($builder)) {
            return;
        }

        $buildDir = $builder->getParameter('kernel.build_dir');
        \assert(\is_string($buildDir));

        // AssetMapper refuses to start on a mapped directory that does not exist.
        $directory = StylesheetCache::directory($buildDir);
        try {
            new Filesystem()->mkdir($directory);
        } catch (IOExceptionInterface $e) {
            throw new RuntimeException(\sprintf('Could not create the design token stylesheet directory "%s".', $directory), previous: $e);
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    $directory => self::ASSET_NAMESPACE,
                ],
            ],
        ]);
    }

    private function isAssetMapperAvailable(ContainerBuilder $builder): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // Before Symfony 8.2, FrameworkBundle provided the AssetMapper configuration.
        if (!$builder->hasParameter('kernel.bundles_metadata')) {
            return false;
        }
        $bundlesMetadata = $builder->getParameter('kernel.bundles_metadata');
        if (!\is_array($bundlesMetadata)) {
            return false;
        }
        if (isset($bundlesMetadata['AssetMapperBundle'])) {
            return true;
        }

        $framework = $bundlesMetadata['FrameworkBundle'] ?? null;
        $path = \is_array($framework) ? $framework['path'] ?? null : null;

        return \is_string($path) && is_file($path.'/Resources/config/asset_mapper.php');
    }

    private function resolveProjectPath(string $path, string $projectDir, ContainerBuilder $builder): string
    {
        if ($this->holdsEnvPlaceholder($path, $builder)) {
            return $path;
        }

        $resolved = $builder->getParameterBag()->resolveValue($path);
        if (!\is_string($resolved)) {
            throw new InvalidArgumentException(\sprintf('The design token path "%s" must resolve to a string.', $path));
        }

        return Path::isAbsolute($resolved) ? $resolved : Path::join($projectDir, $resolved);
    }
}
