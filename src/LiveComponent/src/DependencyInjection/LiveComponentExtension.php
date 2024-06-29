<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\DependencyInjection;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentValidator;
use Symfony\UX\LiveComponent\ComponentValidatorInterface;
use Symfony\UX\LiveComponent\Controller\BatchActionController;
use Symfony\UX\LiveComponent\EventListener\AddLiveAttributesSubscriber;
use Symfony\UX\LiveComponent\EventListener\DataModelPropsSubscriber;
use Symfony\UX\LiveComponent\EventListener\DeferLiveComponentSubscriber;
use Symfony\UX\LiveComponent\EventListener\InterceptChildComponentRenderSubscriber;
use Symfony\UX\LiveComponent\EventListener\LiveComponentSubscriber;
use Symfony\UX\LiveComponent\EventListener\QueryStringInitializeSubscriber;
use Symfony\UX\LiveComponent\EventListener\ResetDeterministicIdSubscriber;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\LiveResponder;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Twig\DeterministicTwigIdCalculator;
use Symfony\UX\LiveComponent\Twig\LiveComponentExtension as LiveComponentTwigExtension;
use Symfony\UX\LiveComponent\Twig\LiveComponentRuntime;
use Symfony\UX\LiveComponent\Twig\TemplateCacheWarmer;
use Symfony\UX\LiveComponent\Twig\TemplateMap;
use Symfony\UX\LiveComponent\Util\ChildComponentPartialRenderer;
use Symfony\UX\LiveComponent\Util\FingerprintCalculator;
use Symfony\UX\LiveComponent\Util\LiveComponentStack;
use Symfony\UX\LiveComponent\Util\LiveControllerAttributesCreator;
use Symfony\UX\LiveComponent\Util\QueryStringPropsExtractor;
use Symfony\UX\LiveComponent\Util\TwigAttributeHelperFactory;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentRenderer;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LiveComponentExtension extends Extension implements PrependExtensionInterface
{
    public const TEMPLATES_MAP_FILENAME = 'live_components_twig_templates.map';

    public function prepend(ContainerBuilder $container)
    {
        // Register the form theme if TwigBundle is available
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['TwigBundle'])) {
            $container->prependExtensionConfig('twig', ['form_themes' => ['@LiveComponent/form_theme.html.twig']]);
        }

        if ($this->isAssetMapperAvailable($container)) {
            $container->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        __DIR__.'/../../assets/dist' => '@symfony/ux-live-component',
                    ],
                ],
            ]);
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            AsLiveComponent::class,
            function (ChildDefinition $definition, AsLiveComponent $attribute) {
                $definition
                    ->addTag('twig.component', array_filter($attribute->serviceConfig(), static fn ($v) => null !== $v && '' !== $v))
                    ->addTag('controller.service_arguments')
                ;
            }
        );

        $container->registerForAutoconfiguration(HydrationExtensionInterface::class)
            ->addTag(LiveComponentBundle::HYDRATION_EXTENSION_TAG);

        $container->register('ux.live_component.component_hydrator', LiveComponentHydrator::class)
            ->setArguments([
                tagged_iterator(LiveComponentBundle::HYDRATION_EXTENSION_TAG),
                new Reference('property_accessor'),
                new Reference('ux.live_component.metadata_factory'),
                new Reference('serializer', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                '%kernel.secret%',
            ])
        ;

        $container->register('ux.live_component.batch_action_controller', BatchActionController::class)
            ->setPublic(true)
            ->setArguments([
                new Reference('http_kernel'),
            ])
        ;

        $container->register('ux.live_component.event_listener.data_model_props_subscriber', DataModelPropsSubscriber::class)
            ->addTag('kernel.event_subscriber')
            ->setArguments([
                new Reference('ux.twig_component.live_component_stack'),
                new Reference('property_accessor'),
            ])
        ;

        $container->register('ux.live_component.event_subscriber', LiveComponentSubscriber::class)
            ->addTag('kernel.event_subscriber')
            ->addTag('container.service_subscriber', ['key' => ComponentFactory::class, 'id' => 'ux.twig_component.component_factory'])
            ->addTag('container.service_subscriber', ['key' => ComponentRenderer::class, 'id' => 'ux.twig_component.component_renderer'])
            ->addTag('container.service_subscriber', ['key' => LiveComponentHydrator::class, 'id' => 'ux.live_component.component_hydrator'])
            ->addTag('container.service_subscriber', ['key' => LiveComponentMetadataFactory::class, 'id' => 'ux.live_component.metadata_factory'])
            ->addTag('container.service_subscriber') // csrf
        ;

        $container->register('ux.live_component.live_responder', LiveResponder::class);
        $container->setAlias(LiveResponder::class, 'ux.live_component.live_responder');

        $container->register('ux.twig_component.live_component_stack', LiveComponentStack::class)
            ->setArguments([
                new Reference('ux.twig_component.component_stack'),
            ])
        ;

        $container->register('ux.live_component.intercept_child_component_render_subscriber', InterceptChildComponentRenderSubscriber::class)
            ->setArguments([
                new Reference('ux.twig_component.live_component_stack'),
            ])
            ->addTag('container.service_subscriber', ['key' => DeterministicTwigIdCalculator::class, 'id' => 'ux.live_component.deterministic_id_calculator'])
            ->addTag('container.service_subscriber', ['key' => ChildComponentPartialRenderer::class, 'id' => 'ux.live_component.child_component_partial_renderer'])
            ->addTag('kernel.event_subscriber');

        $container->register('ux.live_component.child_component_partial_renderer', ChildComponentPartialRenderer::class)
            ->setArguments([
                new Reference('ux.live_component.fingerprint_calculator'),
                new Reference('ux.live_component.attribute_helper_factory'),
            ])
            ->addTag('container.service_subscriber', ['key' => ComponentFactory::class, 'id' => 'ux.twig_component.component_factory'])
            ->addTag('container.service_subscriber', ['key' => LiveComponentMetadataFactory::class, 'id' => 'ux.live_component.metadata_factory'])
            ->addTag('container.service_subscriber', ['key' => LiveComponentHydrator::class, 'id' => 'ux.live_component.component_hydrator'])
            ->addTag('container.service_subscriber', ['key' => LiveControllerAttributesCreator::class, 'id' => 'ux.live_component.live_controller_attributes_creator'])
        ;

        $container->register('ux.live_component.reset_deterministic_id_subscriber', ResetDeterministicIdSubscriber::class)
            ->setArguments([
                new Reference('ux.live_component.deterministic_id_calculator'),
                new Reference('ux.twig_component.component_stack'),
            ])
            ->addTag('kernel.event_subscriber');

        $container->register('ux.live_component.twig.component_extension', LiveComponentTwigExtension::class)
            ->addTag('twig.extension')
        ;

        $container->register('ux.live_component.twig.component_runtime', LiveComponentRuntime::class)
            ->setArguments([
                new Reference('ux.live_component.component_hydrator'),
                new Reference('ux.twig_component.component_factory'),
                new Reference('router'),
                new Reference('ux.live_component.metadata_factory'),
                new Reference('stimulus.helper'),
            ])
            ->addTag('twig.runtime')
        ;

        $container->register('ux.live_component.metadata_factory', LiveComponentMetadataFactory::class)
            ->setArguments([
                new Reference('ux.twig_component.component_factory'),
                new Reference('property_info'),
            ])
            ->addTag('kernel.reset', ['method' => 'reset'])
        ;

        $container->register(ComponentValidator::class)
            ->addTag('container.service_subscriber', ['key' => 'validator', 'id' => 'validator'])
        ;

        $container->register('ux.live_component.attribute_helper_factory', TwigAttributeHelperFactory::class)
            ->setArguments([new Reference('twig')]);

        $container->register('ux.live_component.live_controller_attributes_creator', LiveControllerAttributesCreator::class)
            ->setArguments([
                new Reference('ux.live_component.metadata_factory'),
                new Reference('ux.live_component.component_hydrator'),
                new Reference('ux.live_component.attribute_helper_factory'),
                new Reference('ux.live_component.deterministic_id_calculator'),
                new Reference('ux.live_component.fingerprint_calculator'),
                new Reference('router'),
                new Reference('ux.live_component.live_responder'),
                new Reference('security.csrf.token_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference('ux.live_component.twig.template_mapper'),
            ])
        ;

        $container->register('ux.live_component.add_attributes_subscriber', AddLiveAttributesSubscriber::class)
            ->setArguments([
                new Reference('ux.twig_component.component_stack'),
                new Reference('ux.live_component.twig.template_mapper'),
            ])
            ->addTag('kernel.event_subscriber')
            ->addTag('container.service_subscriber', ['key' => LiveControllerAttributesCreator::class, 'id' => 'ux.live_component.live_controller_attributes_creator'])
        ;

        $container->register('ux.live_component.query_string_props_extractor', QueryStringPropsExtractor::class)
            ->setArguments([
                new Reference('ux.live_component.component_hydrator'),
            ]);

        $container->register('ux.live_component.query_string_initializer_subscriber', QueryStringInitializeSubscriber::class)
            ->setArguments([
                new Reference('request_stack'),
                new Reference('ux.live_component.metadata_factory'),
                new Reference('ux.live_component.query_string_props_extractor'),
                new Reference('property_accessor'),
            ])
            ->addTag('kernel.event_subscriber');

        $container->register('ux.live_component.defer_live_component_subscriber', DeferLiveComponentSubscriber::class)
            ->setArguments([
                new Reference('ux.twig_component.component_stack'),
                new Reference('ux.live_component.live_controller_attributes_creator'),
            ])
            ->addTag('kernel.event_subscriber')
        ;

        $container->register('ux.live_component.deterministic_id_calculator', DeterministicTwigIdCalculator::class);
        $container->register('ux.live_component.fingerprint_calculator', FingerprintCalculator::class)
            ->setArguments(['%kernel.secret%']);

        $container->setAlias(ComponentValidatorInterface::class, ComponentValidator::class);

        $container
            ->setDefinition('form.live_collection', new Definition(LiveCollectionType::class))
            ->addTag('form.type')
            ->setPublic(false)
        ;

        $container->register('ux.live_component.twig.template_mapper', TemplateMap::class)
            ->setArguments(['%kernel.build_dir%/'.self::TEMPLATES_MAP_FILENAME]);

        $container->register('ux.live_component.twig.cache_warmer', TemplateCacheWarmer::class)
            ->setArguments([
                new Reference('twig.template_iterator'),
                self::TEMPLATES_MAP_FILENAME,
                '%kernel.secret%',
            ])
            ->addTag('kernel.cache_warmer');
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
