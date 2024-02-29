<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\EventListener;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\UX\LiveComponent\Twig\TemplateMap;
use Symfony\UX\LiveComponent\Util\LiveControllerAttributesCreator;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Symfony\UX\TwigComponent\MountedComponent;

/**
 * Adds the extra attributes needed to activate a live controller.
 *
 * Used during initial render and a re-render of a component.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class AddLiveAttributesSubscriber implements EventSubscriberInterface, ServiceSubscriberInterface
{
    public function __construct(
        private ComponentStack $componentStack,
        private TemplateMap $templateMap,
        private ContainerInterface $container,
    ) {
    }

    public function onPreRender(PreRenderEvent $event): void
    {
        if (!$event->getMetadata()->get('live', false)) {
            // not a live component, skip
            return;
        }

        $metadata = $event->getMetadata();
        $attributes = $this->getLiveAttributes($event->getMountedComponent(), $metadata);
        $variables = $event->getVariables();
        $attributesKey = $metadata->getAttributesVar();

        // the original ComponentAttributes have already been processed and set
        // onto the variables. So, we manually merge our new attributes in and
        // override that variable.
        if (isset($variables[$attributesKey]) && $variables[$attributesKey] instanceof ComponentAttributes) {
            $originalAttributes = $variables[$attributesKey]->all();

            // merge with existing attributes if available
            $attributes = $attributes->defaults($originalAttributes);

            if (isset($originalAttributes['data-host-template'], $originalAttributes['data-embedded-template-index'])) {
                // This component is an embedded component, that's being re-rendered.
                // We'll change the template that will be used to render it to
                // the embedded template so that the blocks from that template
                // will be used, if any, instead of the originals.
                $event->setTemplate(
                    $this->templateMap->resolve($originalAttributes['data-host-template']),
                    $originalAttributes['data-embedded-template-index'],
                );
            }
        }

        // "key" is a special attribute: don't actually render it
        // this is used inside LiveControllerAttributesCreator
        $attributes = $attributes->without(LiveControllerAttributesCreator::KEY_PROP_NAME);

        $variables[$attributesKey] = $attributes;

        $event->setVariables($variables);
    }

    public static function getSubscribedEvents(): array
    {
        return [PreRenderEvent::class => 'onPreRender'];
    }

    public static function getSubscribedServices(): array
    {
        return [
            LiveControllerAttributesCreator::class,
        ];
    }

    private function getLiveAttributes(MountedComponent $mounted, ComponentMetadata $metadata): ComponentAttributes
    {
        $attributesCreator = $this->container->get(LiveControllerAttributesCreator::class);
        \assert($attributesCreator instanceof LiveControllerAttributesCreator);

        $attributesCollection = $attributesCreator->attributesForRendering(
            $mounted,
            $metadata,
            $this->componentStack->hasParentComponent()
        );

        return new ComponentAttributes($attributesCollection->toEscapedArray());
    }
}
