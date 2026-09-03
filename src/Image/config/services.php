<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\UX\Image\Async\ImageProcessingDispatcherInterface;
use Symfony\UX\Image\Command\RegenerateVariantsCommand;
use Symfony\UX\Image\Command\ValidateConfigurationCommand;
use Symfony\UX\Image\ConfigurationReporter;
use Symfony\UX\Image\ConfigurationValidator;
use Symfony\UX\Image\DataCollector\ImageConfigurationCollector;
use Symfony\UX\Image\ProcessingLimits;
use Symfony\UX\Image\Processor\ChainImageProcessor;
use Symfony\UX\Image\Processor\GdImageProcessor;
use Symfony\UX\Image\Processor\ImageDriverInterface;
use Symfony\UX\Image\Processor\ImageInspector;
use Symfony\UX\Image\Processor\ImageInspectorInterface;
use Symfony\UX\Image\Processor\ImageProcessorInterface;
use Symfony\UX\Image\Processor\InterventionImageProcessor;
use Symfony\UX\Image\Profile\ProfileRegistry;
use Symfony\UX\Image\Regeneration\RegenerationServiceResolver;
use Symfony\UX\Image\Renderer\DefaultImageRenderer;
use Symfony\UX\Image\Renderer\ImageRendererInterface;
use Symfony\UX\Image\Storage\LocalStorage;
use Symfony\UX\Image\Storage\StorageInterface;
use Symfony\UX\Image\Svg\RejectSvgPolicy;
use Symfony\UX\Image\Svg\SvgPolicyInterface;
use Symfony\UX\Image\Transformation\ResizeGeometryCalculator;
use Symfony\UX\Image\Twig\ImageExtension;
use Symfony\UX\Image\Twig\ImageRuntime;
use Symfony\UX\Image\UrlGenerator\CachedUrlGenerator;
use Symfony\UX\Image\UrlGenerator\CloudinaryUrlBuilder;
use Symfony\UX\Image\UrlGenerator\GenericUrlAdapter;
use Symfony\UX\Image\UrlGenerator\ImgixUrlBuilder;
use Symfony\UX\Image\UrlGenerator\StorageUrlAdapter;
use Symfony\UX\Image\UrlGenerator\UrlGenerator;
use Symfony\UX\Image\UrlGenerator\UrlGeneratorInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->private()
    ;

    $container->parameters()
        ->set('ux_image.storage_root', '%kernel.project_dir%/var/ux-image')
    ;

    // -- Storage -------------------------------------------------------------

    $services->set('ux_image.storage.local', LocalStorage::class)
        ->arg('$storages', param('ux_image.storages'))
        ->arg('$storageRoot', param('ux_image.storage_root'))
        ->arg('$limits', service(ProcessingLimits::class))
    ;

    $services->alias(StorageInterface::class, 'ux_image.storage.local');
    $services->set(ProcessingLimits::class)
        ->args([
            param('ux_image.limits.max_input_bytes'),
            param('ux_image.limits.max_width'),
            param('ux_image.limits.max_height'),
            param('ux_image.limits.max_pixels'),
            param('ux_image.limits.max_variants'),
            param('ux_image.limits.max_output_pixels'),
        ])
    ;
    $services->set(ProfileRegistry::class)
        ->args([param('ux_image.profiles')])
    ;
    // -- Processors ----------------------------------------------------------

    $services->set('ux_image.processor.gd', GdImageProcessor::class)
        ->arg('$storageManager', service(StorageInterface::class))
        ->arg('$profiles', param('ux_image.profiles'))
        ->arg('$imageInspector', service(ImageInspectorInterface::class))
        ->arg('$svgPolicy', service(SvgPolicyInterface::class))
        ->arg('$geometryCalculator', service(ResizeGeometryCalculator::class))
        ->arg('$asyncDispatcher', service(ImageProcessingDispatcherInterface::class)->nullOnInvalid())
        ->arg('$limits', service(ProcessingLimits::class))
        ->tag('ux_image.processor')
    ;

    if (interface_exists(\Intervention\Image\Interfaces\ImageManagerInterface::class)) {
        // The driver argument is resolved from configuration in UXImageBundle::loadExtension().
        $services->set('ux_image.image_manager', \Intervention\Image\ImageManager::class)
            ->factory([\Intervention\Image\ImageManager::class, 'withDriver'])
            ->args([abstract_arg('Intervention Image driver, resolved from the "ux_image.driver"/"ux_image.driver_service" configuration')])
        ;

        $services->set('ux_image.processor.intervention', InterventionImageProcessor::class)
            ->arg('$storageManager', service(StorageInterface::class))
            ->arg('$profiles', param('ux_image.profiles'))
            ->arg('$imageInspector', service(ImageInspectorInterface::class))
            ->arg('$imageManager', service('ux_image.image_manager'))
            ->arg('$svgPolicy', service(SvgPolicyInterface::class))
            ->arg('$geometryCalculator', service(ResizeGeometryCalculator::class))
            ->arg('$asyncDispatcher', service(ImageProcessingDispatcherInterface::class)->nullOnInvalid())
            ->arg('$limits', service(ProcessingLimits::class))
            ->tag('ux_image.processor')
        ;
    }

    $services->set('ux_image.processor.chain', ChainImageProcessor::class)
        ->arg('$processors', tagged_iterator('ux_image.processor'))
        ->arg('$defaultDriver', param('ux_image.driver'))
    ;

    $services->alias(ImageProcessorInterface::class, 'ux_image.processor.chain');
    $services->alias(ImageDriverInterface::class, 'ux_image.processor.chain');

    // -- Inspection ----------------------------------------------------------

    $services->set('ux_image.image_inspector', ImageInspector::class);

    $services->alias(ImageInspectorInterface::class, 'ux_image.image_inspector');
    $services->set(RejectSvgPolicy::class);
    $services->alias(SvgPolicyInterface::class, RejectSvgPolicy::class);
    $services->set(ResizeGeometryCalculator::class);

    // -- URL generation ------------------------------------------------------

    $services->set('ux_image.url_builder.cloudinary', CloudinaryUrlBuilder::class)
        ->tag('ux_image.cdn_url_builder', ['provider' => 'cloudinary'])
    ;

    $services->set('ux_image.url_builder.imgix', ImgixUrlBuilder::class)
        ->tag('ux_image.cdn_url_builder', ['provider' => 'imgix'])
    ;

    $services->set('ux_image.url_adapter.generic', GenericUrlAdapter::class)
        ->tag('ux_image.storage_adapter', ['alias' => 'generic'])
    ;

    $services->set('ux_image.url_adapter.storage', StorageUrlAdapter::class)
        ->arg('$storage', service(StorageInterface::class))
        ->tag('ux_image.storage_adapter', ['alias' => 'storage'])
    ;

    $services->set('ux_image.url_generator', UrlGenerator::class)
        ->arg('$cdnUrlBuilders', tagged_iterator('ux_image.cdn_url_builder', 'provider'))
        ->arg('$urlAdapters', tagged_iterator('ux_image.storage_adapter', 'alias'))
        ->arg('$storages', param('ux_image.storages'))
    ;

    $services->set('ux_image.url_generator.cached', CachedUrlGenerator::class)
        ->decorate('ux_image.url_generator')
        ->arg('$decorated', service('.inner'))
        ->arg('$cache', service('%ux_image.cache.pool%'))
        ->arg('$ttl', param('ux_image.cache.ttl'))
        ->arg('$namespace', param('ux_image.cache.namespace'))
    ;

    $services->alias(UrlGeneratorInterface::class, 'ux_image.url_generator');

    // -- Configuration reporting ---------------------------------------------

    $services->set('ux_image.configuration_reporter', ConfigurationReporter::class)
        ->arg('$storages', param('ux_image.storages'))
        ->arg('$profiles', param('ux_image.profiles'))
    ;
    $services->set('ux_image.configuration_validator', ConfigurationValidator::class)
        ->arg('$imageDriver', service(ImageDriverInterface::class))
        ->arg('$storage', service(StorageInterface::class))
        ->arg('$driver', param('ux_image.driver'))
        ->arg('$storages', param('ux_image.storages'))
        ->arg('$profiles', param('ux_image.profiles'))
    ;

    // -- Renderer ------------------------------------------------------------

    $services->set('ux_image.renderer', DefaultImageRenderer::class)
        ->arg('$urlGenerator', service(UrlGeneratorInterface::class))
        ->arg('$defaultSizes', param('ux_image.default_sizes'))
        ->arg('$preferredFormats', param('ux_image.preferred_formats'))
        ->arg('$profiles', service(ProfileRegistry::class))
    ;

    $services->alias(ImageRendererInterface::class, 'ux_image.renderer');

    // -- Twig ----------------------------------------------------------------

    $services->set('ux_image.twig.runtime', ImageRuntime::class)
        ->arg('$renderer', service(ImageRendererInterface::class))
        ->tag('twig.runtime')
    ;

    $services->set('ux_image.twig.extension', ImageExtension::class)
        ->tag('twig.extension')
    ;

    // -- Profiler ------------------------------------------------------------

    $services->set('ux_image.data_collector.configuration', ImageConfigurationCollector::class)
        ->arg('$reporter', service('ux_image.configuration_reporter'))
        ->arg('$storages', param('ux_image.storages'))
        ->arg('$profiles', param('ux_image.profiles'))
        ->tag('data_collector', ['id' => 'ux_image.configuration', 'template' => '@UXImage/data_collector/image_configuration.html.twig'])
    ;

    // -- Commands ------------------------------------------------------------

    $services->set('ux_image.command.validate', ValidateConfigurationCommand::class)
        ->arg('$reporter', service('ux_image.configuration_reporter'))
        ->arg('$validator', service('ux_image.configuration_validator'))
        ->arg('$storages', param('ux_image.storages'))
        ->arg('$profiles', param('ux_image.profiles'))
        ->tag('console.command')
    ;

    $services->set('ux_image.command.regenerate', RegenerateVariantsCommand::class)
        ->arg('$processor', service(ImageProcessorInterface::class))
        ->arg('$profiles', param('ux_image.profiles'))
        ->arg('$services', service(RegenerationServiceResolver::class))
        ->arg('$storage', service(StorageInterface::class))
        ->arg('$storages', param('ux_image.storages'))
        ->tag('console.command')
    ;
    $services->set(RegenerationServiceResolver::class)
        ->args([
            tagged_iterator('ux_image.regeneration.provider'),
            tagged_iterator('ux_image.regeneration.persister'),
        ])
    ;
};
