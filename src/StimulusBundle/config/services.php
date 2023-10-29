<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\StimulusBundle\AssetMapper\AutoImportLocator;
use Symfony\UX\StimulusBundle\AssetMapper\ControllersMapGenerator;
use Symfony\UX\StimulusBundle\AssetMapper\StimulusLoaderJavaScriptCompiler;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Symfony\UX\StimulusBundle\Twig\StimulusTwigExtension;
use Symfony\UX\StimulusBundle\Twig\UxControllersTwigExtension;
use Symfony\UX\StimulusBundle\Twig\UxControllersTwigRuntime;
use Symfony\UX\StimulusBundle\Ux\UxPackageReader;
use Twig\Environment;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('stimulus.helper', StimulusHelper::class)
            ->args([
                service(Environment::class)->nullOnInvalid(),
            ])

        ->set('stimulus.twig_extension', StimulusTwigExtension::class)
            ->args([
                service('stimulus.helper'),
            ])
            // negative priority actually causes the stimulus_ functions from this
            // bundle to be used instead of the ones from WebpackEncoreBundle.
            ->tag('twig.extension', ['priority' => -10])

        ->set('stimulus.asset_mapper.ux_package_reader', UxPackageReader::class)
            ->args([
                param('kernel.project_dir'),
            ])

        // symfony/asset-mapper services
        ->set('stimulus.ux_controllers_twig_extension', UxControllersTwigExtension::class)
            ->tag('twig.extension')

        ->set('stimulus.ux_controllers_twig_runtime', UxControllersTwigRuntime::class)
            ->args([
                service('stimulus.asset_mapper.controllers_map_generator'),
                service('asset_mapper'),
                service('stimulus.asset_mapper.ux_package_reader'),
                param('kernel.project_dir'),
            ])
            ->tag('twig.runtime')

        ->set('stimulus.asset_mapper.controllers_map_generator', ControllersMapGenerator::class)
            ->args([
                service('asset_mapper'),
                service('stimulus.asset_mapper.ux_package_reader'),
                abstract_arg('controller paths'),
                abstract_arg('controllers_json_path'),
                // @legacy - only allowing null for framework-bundle 6.3
                service('stimulus.asset_mapper.auto_import_locator')->nullOnInvalid(),
            ])

        // @legacy - is removed in 6.3
        ->set('stimulus.asset_mapper.auto_import_locator', AutoImportLocator::class)
            ->args([
                service('asset_mapper.importmap.config_reader'),
                service('asset_mapper'),
            ])

        ->set('stimulus.asset_mapper.loader_javascript_compiler', StimulusLoaderJavaScriptCompiler::class)
            ->args([
                service('stimulus.asset_mapper.controllers_map_generator'),
                param('kernel.debug'),
            ])
            ->tag('asset_mapper.compiler', ['priority' => 100])
    ;
};
