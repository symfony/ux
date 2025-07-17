<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\Map\Bridge as MapBridge;
use Symfony\UX\Map\Renderer\AbstractRendererFactory;
use Symfony\UX\Map\Renderer\NullRendererFactory;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class UXMapBundle extends AbstractBundle
{
    protected string $extensionAlias = 'ux_map';

    /**
     * @var array<string, array{ renderer_factory: class-string<AbstractRendererFactory> }>
     *
     * @internal
     */
    public static array $bridges = [
        'google' => ['renderer_factory' => MapBridge\Google\Renderer\GoogleRendererFactory::class],
        'leaflet' => ['renderer_factory' => MapBridge\Leaflet\Renderer\LeafletRendererFactory::class],
    ];

    public function configure(DefinitionConfigurator $definition): void
    {
        $rootNode = $definition->rootNode();
        $rootNode
            ->children()
                ->scalarNode('renderer')->defaultNull()->end()
                ->arrayNode('google_maps')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_map_id')->defaultNull()->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        if (!isset($config['renderer'])) {
            $config['renderer'] = 'null://null';
        }

        if (ContainerBuilder::willBeAvailable('symfony/ux-twig-component', TwigComponentBundle::class, ['symfony/ux-map'])) {
            $container->import('../config/twig_component.php');
        }

        if (str_starts_with($config['renderer'], 'null://')) {
            $container->services()
                ->set('ux_map.renderer_factory.null', NullRendererFactory::class)
                ->arg(0, array_map(fn ($name) => 'symfony/ux-'.$name.'-map', array_keys(self::$bridges)))
                ->tag('ux_map.renderer_factory');
        }

        $renderers = ['default' => $config['renderer']];
        $container->services()
            ->get('ux_map.renderers')
            ->arg(0, $renderers);

        foreach (self::$bridges as $name => $bridge) {
            if (ContainerBuilder::willBeAvailable('symfony/ux-'.$name.'-map', $bridge['renderer_factory'], ['symfony/ux-map'])) {
                $container->services()
                    ->set($rendererFactoryName = 'ux_map.renderer_factory.'.$name, $bridge['renderer_factory'])
                    ->parent('ux_map.renderer_factory.abstract')
                    ->tag('ux_map.renderer_factory');

                if ('google' === $name) {
                    $container->services()
                        ->get($rendererFactoryName)
                        ->args([
                            '$defaultMapId' => $config['google_maps']['default_map_id'],
                        ]);
                }
            }
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!$this->isAssetMapperAvailable()) {
            return;
        }

        $paths = [
            __DIR__.'/../assets/dist' => '@symfony/ux-map',
        ];

        foreach (self::$bridges as $name => $bridge) {
            if (ContainerBuilder::willBeAvailable('symfony/ux-'.$name.'-map', $bridge['renderer_factory'], ['symfony/ux-map'])) {
                $rendererFactoryReflection = new \ReflectionClass($bridge['renderer_factory']);
                $bridgePath = \dirname($rendererFactoryReflection->getFileName(), 3);
                $paths[$bridgePath.'/assets/dist'] = '@symfony/ux-'.$name.'-map';
            }
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => $paths,
            ],
        ]);
    }

    private function isAssetMapperAvailable(): bool
    {
        return interface_exists(AssetMapperInterface::class);
    }
}
