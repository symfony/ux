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
use Symfony\UX\LiveComponent\Twig\DeterministicTwigIdCalculator;
use Symfony\UX\LiveComponent\Util\ChildComponentPartialRenderer;
use Symfony\UX\LiveComponent\Util\LiveControllerAttributesCreator;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\Event\PreCreateForRenderEvent;

/**
 * Responsible for rendering children as empty elements during a re-render.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 *
 * @internal
 */
class InterceptChildComponentRenderSubscriber implements EventSubscriberInterface, ServiceSubscriberInterface
{
    public const CHILDREN_FINGERPRINTS_METADATA_KEY = 'children_fingerprints';

    public function __construct(
        private ComponentStack $componentStack,
        private ContainerInterface $container,
    ) {
    }

    public function preComponentCreated(PreCreateForRenderEvent $event): void
    {
        // if there is already a component, that's a parent. Else, this is not a child.
        if (null === $parentComponent = $this->componentStack->getCurrentComponent()) {
            return;
        }

        if (!$parentComponent->hasExtraMetadata(self::CHILDREN_FINGERPRINTS_METADATA_KEY)) {
            return;
        }

        $childFingerprints = $parentComponent->getExtraMetadata(self::CHILDREN_FINGERPRINTS_METADATA_KEY);

        // get the deterministic id for this child, but without incrementing the counter yet
        $deterministicId = $event->getProps()['data-live-id'] ?? $this->getDeterministicIdCalculator()->calculateDeterministicId(increment: false);
        if (!isset($childFingerprints[$deterministicId])) {
            // child fingerprint wasn't set, it is likely a new child, allow it to render fully
            return;
        }

        // increment the internal counter now to keep "counter" consistency if we're
        // in a loop of children being rendered on the same line
        // we need to do this because this component will *not* ever hit
        // AddLiveAttributesSubscriber where the counter is normally incremented
        $this->getDeterministicIdCalculator()->calculateDeterministicId(increment: true);

        $childPartialRenderer = $this->container->get(ChildComponentPartialRenderer::class);
        \assert($childPartialRenderer instanceof ChildComponentPartialRenderer);

        $rendered = $childPartialRenderer->renderChildComponent(
            $deterministicId,
            $childFingerprints[$deterministicId],
            $event->getName(),
            $event->getProps(),
        );
        $event->setRenderedString($rendered);
    }

    public static function getSubscribedServices(): array
    {
        return [
            DeterministicTwigIdCalculator::class,
            ChildComponentPartialRenderer::class,
        ];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreCreateForRenderEvent::class => 'preComponentCreated',
        ];
    }

    private function getDeterministicIdCalculator(): DeterministicTwigIdCalculator
    {
        return $this->container->get(DeterministicTwigIdCalculator::class);
    }

    private function getAttributesCreator(): LiveControllerAttributesCreator
    {
        return $this->container->get(LiveControllerAttributesCreator::class);
    }
}
