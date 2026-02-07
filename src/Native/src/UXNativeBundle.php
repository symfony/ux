<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\Native\Attribute\AsNativeConfigurationProvider;
use Symfony\UX\Native\Maker\MakeNativeBridgeController;

class UXNativeBundle extends AbstractBundle
{
    protected string $extensionAlias = 'ux_native';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('output_dir')
                    ->defaultNull()
                    ->info('Directory where configuration JSON files are written. Defaults to %kernel.project_dir%/public.')
                ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigurationCompilerPass());

        $container->registerAttributeForAutoconfiguration(AsNativeConfigurationProvider::class, static function (ChildDefinition $definition): void {
            $definition->addTag('ux_native.configuration_provider');
        });
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $outputDir = $config['output_dir'] ?? $builder->getParameter('kernel.project_dir').'/public';
        $builder->setParameter('ux_native.output_dir', $outputDir);

        $container->import('../config/services.php');

        if ($builder->hasParameter('kernel.debug') && $builder->getParameter('kernel.debug')) {
            $container->import('../config/debug.php');
        }

        $container->services()
            ->set('ux.native.make_native_bridge_controller', MakeNativeBridgeController::class)
            ->tag('maker.command')
            ->args(['%kernel.project_dir%'])
        ;
    }
}
