<?php

namespace Symfony\UX\LiveComponent\EventListener;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\UX\LiveComponent\Twig\LiveComponentRuntime;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\EventListener\PreRenderEvent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AddLiveAttributesSubscriber implements EventSubscriberInterface, ServiceSubscriberInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function onPreRender(PreRenderEvent $event): void
    {
        if (!$event->getMetadata()->get('live', false)) {
            // not a live component, skip
            return;
        }

        /** @var ComponentAttributes $attributes */
        $attributes = $this->container->get(LiveComponentRuntime::class)
            ->getLiveAttributes($event->getComponent(), $event->getMetadata())
        ;

        $variables = $event->getVariables();

        if (isset($variables['attributes']) && $variables['attributes'] instanceof ComponentAttributes) {
            // merge with existing attributes if available
            $attributes = $attributes->defaults($variables['attributes']->all());
        }

        $variables['attributes'] = $attributes;

        $event->setVariables($variables);
    }

    public static function getSubscribedEvents(): array
    {
        return [PreRenderEvent::class => 'onPreRender'];
    }

    public static function getSubscribedServices(): array
    {
        return [
            LiveComponentRuntime::class,
        ];
    }
}
