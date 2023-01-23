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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\UX\LiveComponent\DehydratedComponent;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Twig\DeterministicTwigIdCalculator;
use Symfony\UX\LiveComponent\Util\FingerprintCalculator;
use Symfony\UX\LiveComponent\Util\JsonUtil;
use Symfony\UX\LiveComponent\Util\TwigAttributeHelper;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Symfony\UX\TwigComponent\MountedComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @internal
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

        if (method_exists($event, 'isEmbedded') && $event->isEmbedded()) {
            // TODO: remove method_exists once min ux-twig-component version has this method
            throw new \LogicException('Embedded components cannot be live.');
        }

        $metadata = $event->getMetadata();
        $attributes = $this->getLiveAttributes($event->getMountedComponent(), $metadata);
        $variables = $event->getVariables();
        $attributesKey = $metadata->getAttributesVar();

        // the original ComponentAttributes have already been processed and set
        // onto the variables. So, we manually merge our new attributes in and
        // override that variable.
        if (isset($variables[$attributesKey]) && $variables[$attributesKey] instanceof ComponentAttributes) {
            // merge with existing attributes if available
            $attributes = $attributes->defaults($variables[$attributesKey]->all());
        }

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
            LiveComponentHydrator::class,
            UrlGeneratorInterface::class,
            TwigAttributeHelper::class,
            ComponentStack::class,
            DeterministicTwigIdCalculator::class,
            FingerprintCalculator::class,
            '?'.CsrfTokenManagerInterface::class,
        ];
    }

    private function getLiveAttributes(MountedComponent $mounted, ComponentMetadata $metadata): ComponentAttributes
    {
        $name = $mounted->getName();
        $url = $this->container->get(UrlGeneratorInterface::class)->generate($metadata->get('route'), ['_live_component' => $name]);
        /** @var DehydratedComponent $dehydratedComponent */
        $dehydratedComponent = $this->container->get(LiveComponentHydrator::class)->dehydrate($mounted);
        /** @var TwigAttributeHelper $helper */
        $helper = $this->container->get(TwigAttributeHelper::class);

        $attributes = [
            'data-controller' => 'live',
            'data-live-url-value' => $helper->escapeAttribute($url),
            'data-live-data-value' => $helper->escapeAttribute(JsonUtil::encodeObject($dehydratedComponent->getData())),
            'data-live-props-value' => $helper->escapeAttribute(JsonUtil::encodeObject($dehydratedComponent->getProps())),
        ];

        if ($this->container->has(CsrfTokenManagerInterface::class) && $metadata->get('csrf')) {
            $attributes['data-live-csrf-value'] = $this->container->get(CsrfTokenManagerInterface::class)
                ->getToken($name)->getValue()
            ;
        }

        if ($this->container->get(ComponentStack::class)->hasParentComponent()) {
            if (!isset($mounted->getAttributes()->all()['data-live-id'])) {
                $id = $this->container->get(DeterministicTwigIdCalculator::class)->calculateDeterministicId();
                $attributes['data-live-id'] = $helper->escapeAttribute($id);
            }

            $fingerprint = $this->container->get(FingerprintCalculator::class)->calculateFingerprint($mounted->getInputProps());
            $attributes['data-live-fingerprint-value'] = $helper->escapeAttribute($fingerprint);
        }

        return new ComponentAttributes($attributes);
    }
}
