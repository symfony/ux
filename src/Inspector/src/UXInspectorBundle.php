<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Inspector;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\Inspector\Controller\InspectorController;
use Symfony\UX\Inspector\EventListener\InjectInspectorListener;
use Symfony\UX\Inspector\Routing\InspectorRouteLoader;

/**
 * Registers the inspector, which only ever runs while kernel.debug is true.
 *
 * The "enabled" option can turn it off within debug, but never turn it on
 * outside it. When it resolves to false the response listener and the route
 * loader are kept as inert services and the controller is not registered at all,
 * so nothing is reachable even if the routes are imported unconditionally.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class UXInspectorBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')
                    ->defaultTrue()
                    ->info('Enable the inspector. It stays off when kernel.debug is false.')
                ->end()
                ->booleanNode('pull_tab')
                    ->defaultTrue()
                    ->info('Display a small pull tab on the right edge of the screen to open the Inspector.')
                ->end()
                ->arrayNode('exclude_paths')
                    ->info('URL path prefixes where the inspector will not be injected')
                    ->scalarPrototype()->end()
                    ->defaultValue(['/_profiler', '/_wdt', '/_error', '/_fragment'])
                ->end()
                ->arrayNode('ignore_selectors')
                    ->info('CSS selectors for DOM elements to skip during component detection')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
            ->end()
        ;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $debug = $builder->hasParameter('kernel.debug') ? (bool) $builder->getParameter('kernel.debug') : false;
        $enabled = $config['enabled'] && $debug;

        $inspectorConfig = [
            'pull_tab' => $config['pull_tab'],
            'ignore_selectors' => $config['ignore_selectors'],
            'packages' => [
                'stimulus' => class_exists('Symfony\\UX\\StimulusBundle\\StimulusBundle'),
                'livecomponent' => class_exists('Symfony\\UX\\LiveComponent\\LiveComponentBundle'),
                'turbo' => class_exists('Symfony\\UX\\Turbo\\TurboBundle'),
            ],
        ];

        /** @var list<string> $excludePaths */
        $excludePaths = $config['exclude_paths'];

        $container->services()
            ->set(InspectorRouteLoader::class)
            ->arg('$enabled', $enabled)
            ->tag('routing.route_loader');

        if ($enabled) {
            $container->services()
                ->set(InspectorController::class)
                ->tag('controller.service_arguments');
        }

        $container->services()
            ->set(InjectInspectorListener::class)
            ->arg('$enabled', $enabled)
            ->arg('$config', $inspectorConfig)
            ->arg('$excludePaths', $excludePaths)
            ->arg('$urlGenerator', new Reference('router'))
            ->tag('kernel.event_listener', [
                'event' => 'kernel.response',
                'priority' => -128,
            ])
        ;
    }
}
