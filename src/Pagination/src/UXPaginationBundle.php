<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\Pagination\Adapter\DoctrineDbalAdapter;
use Symfony\UX\Pagination\Adapter\DoctrineOrmAdapter;
use Symfony\UX\Pagination\Adapter\PaginationAdapterInterface;
use Symfony\UX\Pagination\Navigation\NavigationMode;

/**
 * @phpstan-type NavigationConfig array{mode: NavigationMode, size: int}
 * @phpstan-type PaginatorConfig array{
 *     items_per_page: int,
 *     max_offset: int,
 *     page_parameter: string,
 *     cursor_parameter: string,
 *     navigation: NavigationConfig,
 * }
 * @phpstan-type NamedPaginatorConfig array{
 *     items_per_page?: int,
 *     max_offset?: int,
 *     page_parameter?: string,
 *     cursor_parameter?: string,
 *     navigation?: array{mode?: NavigationMode, size?: int},
 * }
 * @phpstan-type BundleConfig array{
 *     items_per_page: int,
 *     max_offset: int,
 *     page_parameter: string,
 *     cursor_parameter: string,
 *     navigation: NavigationConfig,
 *     theme: string,
 *     cursor: array{secret: ?string},
 *     paginators: array<string, NamedPaginatorConfig>,
 * }
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class UXPaginationBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(PaginationAdapterInterface::class)
            ->addTag('ux_pagination.adapter');
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('items_per_page')
                    ->defaultValue(20)
                    ->min(1)
                    ->max(\PHP_INT_MAX - 1)
                    ->info('Default number of items per page')
                ->end()
                ->integerNode('max_offset')
                    ->defaultValue(100_000)
                    ->min(0)
                    ->info('Maximum offset accepted by numbered pagination; use cursor pagination for larger datasets')
                ->end()
                ->scalarNode('page_parameter')
                    ->defaultValue('page')
                    ->cannotBeEmpty()
                    ->info('Query parameter name for page number')
                    ->validate()
                        ->ifTrue(static fn (mixed $value): bool => !\is_string($value) || '' === trim($value))
                        ->thenInvalid('The query parameter name must be a non-empty string.')
                    ->end()
                ->end()
                ->scalarNode('cursor_parameter')
                    ->defaultValue('cursor')
                    ->cannotBeEmpty()
                    ->info('Query parameter name for the cursor (cursor-based pagination)')
                    ->validate()
                        ->ifTrue(static fn (mixed $value): bool => !\is_string($value) || '' === trim($value))
                        ->thenInvalid('The cursor parameter name must be a non-empty string.')
                    ->end()
                ->end()
                ->arrayNode('navigation')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('mode')
                            ->enumFqcn(NavigationMode::class)
                            ->defaultValue(NavigationMode::Sliding)
                            ->info('Default numbered navigation mode')
                        ->end()
                        ->integerNode('size')
                            ->defaultValue(5)
                            ->min(1)
                            ->info('Default navigation window size')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('theme')
                    ->defaultValue('@UXPagination/theme/default.html.twig')
                    ->cannotBeEmpty()
                    ->info('Twig template used to render pagination')
                    ->validate()
                        ->ifTrue(static fn (mixed $value): bool => !\is_string($value) || '' === trim($value))
                        ->thenInvalid('The pagination theme must be a non-empty Twig template name.')
                    ->end()
                ->end()
                ->arrayNode('cursor')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('secret')
                            ->defaultValue('%kernel.secret%')
                            ->info('The secret used to sign opaque cursors')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('paginators')
                    ->useAttributeAsKey('name')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->children()
                            ->integerNode('items_per_page')->min(1)->max(\PHP_INT_MAX - 1)->end()
                            ->integerNode('max_offset')->min(0)->end()
                            ->scalarNode('page_parameter')
                                ->cannotBeEmpty()
                                ->validate()
                                    ->ifTrue(static fn (mixed $value): bool => !\is_string($value) || '' === trim($value))
                                    ->thenInvalid('The query parameter name must be a non-empty string.')
                                ->end()
                            ->end()
                            ->scalarNode('cursor_parameter')
                                ->cannotBeEmpty()
                                ->validate()
                                    ->ifTrue(static fn (mixed $value): bool => !\is_string($value) || '' === trim($value))
                                    ->thenInvalid('The cursor parameter name must be a non-empty string.')
                                ->end()
                            ->end()
                            ->arrayNode('navigation')
                                ->children()
                                    ->enumNode('mode')->enumFqcn(NavigationMode::class)->end()
                                    ->integerNode('size')->min(1)->end()
                                ->end()
                            ->end()
                            ->end()
                        ->end()
                    ->validate()
                        ->always(static function (array $paginators): array {
                            $targets = [];
                            foreach (array_keys($paginators) as $name) {
                                if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_.-]*$/D', $name)) {
                                    throw new InvalidConfigurationException(\sprintf('Invalid paginator name "%s": use letters, digits, dots, dashes or underscores, and start with a letter or underscore.', $name));
                                }

                                $target = new Target($name)->getParsedName();
                                if (isset($targets[$target])) {
                                    throw new InvalidConfigurationException(\sprintf('Paginator names "%s" and "%s" resolve to the same autowiring target "%s".', $targets[$target], $name, $target));
                                }

                                $targets[$target] = $name;
                            }

                            return $paginators;
                        })
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param BundleConfig $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $cursorSecret = $config['cursor']['secret'] ?? '';
        if ('%kernel.secret%' === $cursorSecret && !$builder->hasParameter('kernel.secret')) {
            $cursorSecret = '';
        }

        $builder->getDefinition('ux_pagination.cursor_codec')
            ->setArgument('$secret', $cursorSecret);

        $paginatorDefaults = [
            'items_per_page' => $config['items_per_page'],
            'max_offset' => $config['max_offset'],
            'page_parameter' => $config['page_parameter'],
            'cursor_parameter' => $config['cursor_parameter'],
            'navigation' => $config['navigation'],
        ];
        $this->configurePaginator($builder->getDefinition('ux_pagination.paginator'), $paginatorDefaults);

        foreach ($config['paginators'] as $name => $paginatorConfig) {
            $serviceId = 'ux_pagination.paginator.'.$name;
            $definition = clone $builder->getDefinition('ux_pagination.paginator');
            $this->configurePaginator($definition->setPublic(false), $this->mergePaginatorConfig($paginatorDefaults, $paginatorConfig));
            $builder->setDefinition($serviceId, $definition);
            $builder->registerAliasForArgument($serviceId, PaginatorInterface::class, $name.'.paginator', $name);
        }

        /** @var array<string, string> $bundles */
        $bundles = $builder->hasParameter('kernel.bundles') ? $builder->getParameter('kernel.bundles') : [];
        if (\is_array($bundles) && isset($bundles['TwigBundle'])) {
            $container->import('../config/twig.php');
            $builder->getDefinition('ux_pagination.renderer')
                ->setArgument('$defaultTheme', $config['theme']);
        }

        if (class_exists(\Doctrine\ORM\QueryBuilder::class)) {
            $definition = new Definition(DoctrineOrmAdapter::class);
            $definition->addTag('ux_pagination.adapter');
            $builder->setDefinition('ux_pagination.adapter.doctrine_orm', $definition);
            $builder->setAlias(DoctrineOrmAdapter::class, 'ux_pagination.adapter.doctrine_orm');
        }

        if (class_exists(\Doctrine\DBAL\Query\QueryBuilder::class)) {
            $definition = new Definition(DoctrineDbalAdapter::class);
            $definition->addTag('ux_pagination.adapter');
            $builder->setDefinition('ux_pagination.adapter.doctrine_dbal', $definition);
            $builder->setAlias(DoctrineDbalAdapter::class, 'ux_pagination.adapter.doctrine_dbal');
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return;
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    \dirname(__DIR__).'/assets/dist' => '@symfony/ux-pagination',
                ],
            ],
        ]);
    }

    /**
     * @param PaginatorConfig $config
     */
    private function configurePaginator(Definition $definition, array $config): void
    {
        $definition
            ->setArgument('$defaultPerPage', $config['items_per_page'])
            ->setArgument('$defaultPageParam', $config['page_parameter'])
            ->setArgument('$defaultCursorParam', $config['cursor_parameter'])
            ->setArgument('$defaultMaxOffset', $config['max_offset'])
            ->setArgument('$defaultNavigationMode', $config['navigation']['mode'])
            ->setArgument('$defaultNavigationSize', $config['navigation']['size'])
        ;
    }

    /**
     * @param PaginatorConfig      $defaults
     * @param NamedPaginatorConfig $config
     *
     * @return PaginatorConfig
     */
    private function mergePaginatorConfig(array $defaults, array $config): array
    {
        return [
            'items_per_page' => $config['items_per_page'] ?? $defaults['items_per_page'],
            'max_offset' => $config['max_offset'] ?? $defaults['max_offset'],
            'page_parameter' => $config['page_parameter'] ?? $defaults['page_parameter'],
            'cursor_parameter' => $config['cursor_parameter'] ?? $defaults['cursor_parameter'],
            'navigation' => [
                'mode' => $config['navigation']['mode'] ?? $defaults['navigation']['mode'],
                'size' => $config['navigation']['size'] ?? $defaults['navigation']['size'],
            ],
        ];
    }
}
