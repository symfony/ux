<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\CacheWarmer\TwigComponentCacheWarmer;
use Symfony\UX\TwigComponent\Command\TwigComponentDebugCommand;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentProperties;
use Symfony\UX\TwigComponent\ComponentRenderer;
use Symfony\UX\TwigComponent\ComponentRendererInterface;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\ComponentTemplateFinder;
use Symfony\UX\TwigComponent\DependencyInjection\Compiler\TwigComponentPass;
use Symfony\UX\TwigComponent\Twig\ComponentExtension;
use Symfony\UX\TwigComponent\Twig\ComponentLexer;
use Symfony\UX\TwigComponent\Twig\ComponentRuntime;
use Symfony\UX\TwigComponent\Twig\TwigEnvironmentConfigurator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class TwigComponentExtension extends Extension implements ConfigurationInterface
{
    private const DEPRECATED_DEFAULT_KEY = '__deprecated__use_old_naming_behavior';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        if (!isset($container->getParameter('kernel.bundles')['TwigBundle'])) {
            throw new LogicException('The TwigBundle is not registered in your application. Try running "composer require symfony/twig-bundle".');
        }

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        $defaults = $config['defaults'];
        if ($defaults === [self::DEPRECATED_DEFAULT_KEY]) {
            trigger_deprecation('symfony/ux-twig-component', '2.13', 'Not setting the "twig_component.defaults" config option is deprecated. Check the documentation for an example configuration.');
            $container->setParameter('ux.twig_component.legacy_autonaming', true);

            $defaults = [];
        }
        $container->setParameter('ux.twig_component.component_defaults', $defaults);

        $container->register('ux.twig_component.component_template_finder', ComponentTemplateFinder::class)
            ->setArguments([
                new Reference('twig.loader'),
                $config['anonymous_template_directory'],
            ]);
        $container->setAlias(ComponentRendererInterface::class, 'ux.twig_component.component_renderer');

        $container->registerAttributeForAutoconfiguration(
            AsTwigComponent::class,
            static function (ChildDefinition $definition, AsTwigComponent $attribute) {
                $definition->addTag('twig.component', array_filter($attribute->serviceConfig()));
            }
        );

        $container->register('ux.twig_component.component_factory', ComponentFactory::class)
            ->setArguments([
                new Reference('ux.twig_component.component_template_finder'),
                new AbstractArgument(\sprintf('Added in %s.', TwigComponentPass::class)),
                new Reference('property_accessor'),
                new Reference('event_dispatcher'),
                new AbstractArgument(\sprintf('Added in %s.', TwigComponentPass::class)),
                new AbstractArgument(\sprintf('Added in %s.', TwigComponentPass::class)),
                new Reference('twig'),
            ])
            ->addTag('kernel.reset', ['method' => 'reset'])
        ;

        $container->register('ux.twig_component.component_stack', ComponentStack::class);

        $container->register('ux.twig_component.component_properties', ComponentProperties::class)
            ->setArguments([
                new Reference('property_accessor'),
                new AbstractArgument(\sprintf('Added in %s.', TwigComponentPass::class)),
                new Reference('cache.ux.twig_component', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ])
        ;

        $container->register('ux.twig_component.component_renderer', ComponentRenderer::class)
            ->setArguments([
                new Reference('twig'),
                new Reference('event_dispatcher'),
                new Reference('ux.twig_component.component_factory'),
                new Reference('ux.twig_component.component_properties'),
                new Reference('ux.twig_component.component_stack'),
            ])
            ->addTag('kernel.reset', ['method' => 'reset'])
        ;

        $container->register('ux.twig_component.twig.component_extension', ComponentExtension::class)
            ->addTag('twig.extension')
        ;

        $container->register('ux.twig_component.twig.component_runtime', ComponentRuntime::class)
            ->setArguments([
                new Reference('ux.twig_component.component_renderer'),
                new ServiceLocatorArgument(new TaggedIteratorArgument('ux.twig_component.twig_renderer', indexAttribute: 'key', needsIndexes: true)),
            ])
            ->addTag('twig.runtime')
        ;

        $container->register('ux.twig_component.twig.lexer', ComponentLexer::class);

        $container->register('ux.twig_component.twig.environment_configurator', TwigEnvironmentConfigurator::class)
            ->setDecoratedService(new Reference('twig.configurator.environment'))
            ->setArguments([new Reference('ux.twig_component.twig.environment_configurator.inner')]);

        $container->register('ux.twig_component.command.debug', TwigComponentDebugCommand::class)
            ->setArguments([
                new Parameter('twig.default_path'),
                new Reference('ux.twig_component.component_factory'),
                new Reference('twig'),
                new AbstractArgument(\sprintf('Added in %s.', TwigComponentPass::class)),
                $config['anonymous_template_directory'],
            ])
            ->addTag('console.command')
        ;

        $container->setAlias('console.command.stimulus_component_debug', 'ux.twig_component.command.debug')
            ->setDeprecated('symfony/ux-twig-component', '2.13', '%alias_id%');

        if ($container->getParameter('kernel.debug') && $config['profiler']) {
            $loader->load('debug.php');
        }

        $loader->load('cache.php');

        $container->register('ux.twig_component.cache_warmer', TwigComponentCacheWarmer::class)
            ->setArguments([new Reference(\Psr\Container\ContainerInterface::class)])
            ->addTag('kernel.cache_warmer')
            ->addTag('container.service_subscriber', ['id' => 'ux.twig_component.component_properties'])
        ;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('twig_component');
        $rootNode = $treeBuilder->getRootNode();
        \assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode
            ->validate()
            ->always(function ($v) {
                if (!isset($v['anonymous_template_directory'])) {
                    trigger_deprecation('symfony/twig-component-bundle', '2.13', 'Not setting the "twig_component.anonymous_template_directory" config option is deprecated. It will default to "components" in 3.0.');
                    $v['anonymous_template_directory'] = null;
                }

                return $v;
            })
            ->end()
            ->children()
                ->arrayNode('defaults')
                    ->defaultValue([self::DEPRECATED_DEFAULT_KEY])
                    ->useAttributeAsKey('namespace')
                    ->validate()
                        ->always(function ($v) {
                            foreach ($v as $namespace => $defaults) {
                                if (!str_ends_with($namespace, '\\')) {
                                    throw new InvalidConfigurationException(\sprintf('The twig_component.defaults namespace "%s" is invalid: it must end in a "\".', $namespace));
                                }
                            }

                            return $v;
                        })
                    ->end()
                    ->arrayPrototype()
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function (string $v) {
                                return ['template_directory' => $v];
                            })
                        ->end()
                        ->children()
                            ->scalarNode('template_directory')
                                ->defaultValue('components')
                            ->end()
                            ->scalarNode('name_prefix')
                                ->defaultValue('')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('anonymous_template_directory')
                    ->info('Defaults to `components`')
                ->end()
                ->booleanNode('profiler')
                    ->info('Enables the profiler for Twig Component (in debug mode)')
                    ->defaultValue('%kernel.debug%')
                ->end()
                ->scalarNode('controllers_json')
                    ->setDeprecated('symfony/ux-twig-component', '2.18', 'The "twig_component.controllers_json" config option is deprecated, and will be removed in 3.0.')
                    ->defaultNull()
                ->end()
            ->end();

        return $treeBuilder;
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return $this;
    }
}
