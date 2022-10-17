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

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentValidator;
use Symfony\UX\LiveComponent\ComponentValidatorInterface;
use Symfony\UX\LiveComponent\Controller\BatchActionController;
use Symfony\UX\LiveComponent\EventListener\AddLiveAttributesSubscriber;
use Symfony\UX\LiveComponent\EventListener\InterceptChildComponentRenderSubscriber;
use Symfony\UX\LiveComponent\EventListener\LiveComponentSubscriber;
use Symfony\UX\LiveComponent\EventListener\ResetDeterministicIdSubscriber;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Twig\DeterministicTwigIdCalculator;
use Symfony\UX\LiveComponent\Twig\LiveComponentExtension as LiveComponentTwigExtension;
use Symfony\UX\LiveComponent\Twig\LiveComponentRuntime;
use Symfony\UX\LiveComponent\Util\FingerprintCalculator;
use Symfony\UX\LiveComponent\Util\TwigAttributeHelper;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentRenderer;
use Symfony\UX\TwigComponent\ComponentStack;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @internal
 */
final class LiveComponentExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        // Register the form theme if TwigBundle is available
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['TwigBundle'])) {
            return;
        }

        $container->prependExtensionConfig('twig', ['form_themes' => ['@LiveComponent/form_theme.html.twig']]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            AsLiveComponent::class,
            function (ChildDefinition $definition, AsLiveComponent $attribute) {
                $definition
                    ->addTag('twig.component', array_filter($attribute->serviceConfig()))
                    ->addTag('controller.service_arguments')
                ;
            }
        );

        $container->register('ux.live_component.component_hydrator', LiveComponentHydrator::class)
            ->setArguments([
                new Reference('serializer'),
                new Reference('property_accessor'),
                '%kernel.secret%',
            ])
        ;

        $container->register('ux.live_component.batch_action_controller', BatchActionController::class)
            ->setPublic(true)
            ->setArguments([
                new Reference('http_kernel'),
            ])
        ;

        $container->register('ux.live_component.event_subscriber', LiveComponentSubscriber::class)
            ->addTag('kernel.event_subscriber')
            ->addTag('container.service_subscriber', ['key' => ComponentFactory::class, 'id' => 'ux.twig_component.component_factory'])
            ->addTag('container.service_subscriber', ['key' => ComponentRenderer::class, 'id' => 'ux.twig_component.component_renderer'])
            ->addTag('container.service_subscriber', ['key' => LiveComponentHydrator::class, 'id' => 'ux.live_component.component_hydrator'])
            ->addTag('container.service_subscriber') // csrf
        ;

        $container->register('ux.live_component.intercept_child_component_render_subscriber', InterceptChildComponentRenderSubscriber::class)
            ->setArguments([
                new Reference('ux.twig_component.component_stack'),
                new Reference('ux.live_component.deterministic_id_calculator'),
                new Reference('ux.live_component.fingerprint_calculator'),
                new Reference('ux.live_component.attribute_helper'),
                new Reference('ux.twig_component.component_factory'),
                new Reference('ux.live_component.component_hydrator'),
            ])
            ->addTag('kernel.event_subscriber');

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
            ])
            ->addTag('twig.runtime')
        ;

        $container->register(ComponentValidator::class)
            ->addTag('container.service_subscriber', ['key' => 'validator', 'id' => 'validator'])
        ;

        $container->register('ux.live_component.attribute_helper', TwigAttributeHelper::class)
            ->setArguments([new Reference('twig')]);

        $container->register('ux.live_component.add_attributes_subscriber', AddLiveAttributesSubscriber::class)
            ->addTag('kernel.event_subscriber')
            ->addTag('container.service_subscriber', ['key' => LiveComponentHydrator::class, 'id' => 'ux.live_component.component_hydrator'])
            ->addTag('container.service_subscriber', ['key' => ComponentStack::class, 'id' => 'ux.twig_component.component_stack'])
            ->addTag('container.service_subscriber', ['key' => TwigAttributeHelper::class, 'id' => 'ux.live_component.attribute_helper'])
            ->addTag('container.service_subscriber', ['key' => DeterministicTwigIdCalculator::class, 'id' => 'ux.live_component.deterministic_id_calculator'])
            ->addTag('container.service_subscriber', ['key' => FingerprintCalculator::class, 'id' => 'ux.live_component.fingerprint_calculator'])
            ->addTag('container.service_subscriber') // csrf & router
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
    }
}
