<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @phpstan-type BundleConfig array{
 *     request_attribute: string,
 *     translation_domain: string|null,
 *     expression_language: string,
 *     theme: string,
 * }
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class UXBreadcrumbBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(RootCrumbProviderInterface::class)
            ->addTag('ux_breadcrumb.root_crumb_provider');
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
                ->scalarNode('request_attribute')
                    ->defaultValue(BreadcrumbTrail::ATTRIBUTE)
                    ->cannotBeEmpty()
                    ->info('Request attribute the collected trail is stored on')
                    ->validate()
                        ->ifTrue(static fn (mixed $value): bool => !\is_string($value) || '' === trim($value))
                        ->thenInvalid('The request attribute must be a non-empty string.')
                    ->end()
                ->end()
                ->scalarNode('translation_domain')
                    ->defaultNull()
                    ->info("Translation domain for crumbs that declare none; null uses the translator's default")
                ->end()
                ->scalarNode('expression_language')
                    ->defaultValue('ux_breadcrumb.expression_language')
                    ->cannotBeEmpty()
                    ->info('Service id of the ExpressionLanguage used to evaluate crumb expressions')
                ->end()
                ->scalarNode('theme')
                    ->defaultValue('@UXBreadcrumb/theme/default.html.twig')
                    ->cannotBeEmpty()
                    ->info('Twig template used by ux_breadcrumb()')
                    ->validate()
                        ->ifTrue(static fn (mixed $value): bool => !\is_string($value) || '' === trim($value))
                        ->thenInvalid('The breadcrumb theme must be a non-empty Twig template name.')
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

        $builder->getDefinition('ux_breadcrumb.listener')
            ->setArgument('$requestAttribute', $config['request_attribute']);

        $builder->getDefinition('ux_breadcrumb.trail_provider')
            ->setArgument('$requestAttribute', $config['request_attribute']);

        $builder->getDefinition('ux_breadcrumb.resolver')
            ->setArgument('$expressionLanguage', new Reference($config['expression_language']))
            ->setArgument('$defaultTranslationDomain', $config['translation_domain']);

        /** @var array<string, string> $bundles */
        $bundles = $builder->hasParameter('kernel.bundles') ? $builder->getParameter('kernel.bundles') : [];
        if (\is_array($bundles) && isset($bundles['TwigBundle'])) {
            $container->import('../config/twig.php');
            $builder->getDefinition('ux_breadcrumb.renderer')
                ->setArgument('$defaultTheme', $config['theme']);
        }
    }
}
