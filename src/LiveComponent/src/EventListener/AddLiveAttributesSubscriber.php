<?php

namespace Symfony\UX\LiveComponent\EventListener;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\EventListener\PreRenderEvent;
use Twig\Environment;

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

        $attributes = $this->getLiveAttributes($event->getComponent(), $event->getMetadata());
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
            LiveComponentHydrator::class,
            UrlGeneratorInterface::class,
            Environment::class,
            '?'.CsrfTokenManagerInterface::class,
        ];
    }

    private function getLiveAttributes(object $component, ComponentMetadata $metadata): ComponentAttributes
    {
        $url = $this->container->get(UrlGeneratorInterface::class)
            ->generate('live_component', ['component' => $metadata->getName()])
        ;
        $data = $this->container->get(LiveComponentHydrator::class)->dehydrate($component);
        $twig = $this->container->get(Environment::class);

        $attributes = [
            'data-controller' => 'live',
            'data-live-url-value' => twig_escape_filter($twig, $url, 'html_attr'),
            'data-live-data-value' => twig_escape_filter($twig, json_encode($data, \JSON_THROW_ON_ERROR), 'html_attr'),
        ];

        if ($this->container->has(CsrfTokenManagerInterface::class)) {
            $attributes['data-live-csrf-value'] = $this->container->get(CsrfTokenManagerInterface::class)
                ->getToken($metadata->getName())->getValue()
            ;
        }

        return new ComponentAttributes($attributes);
    }
}
