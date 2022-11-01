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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Twig\DeterministicTwigIdCalculator;
use Symfony\UX\LiveComponent\Util\FingerprintCalculator;
use Symfony\UX\LiveComponent\Util\TwigAttributeHelper;
use Symfony\UX\TwigComponent\ComponentFactory;
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
class InterceptChildComponentRenderSubscriber implements EventSubscriberInterface
{
    public const CHILDREN_FINGERPRINTS_METADATA_KEY = 'children_fingerprints';

    public function __construct(
        private ComponentStack $componentStack,
        private DeterministicTwigIdCalculator $deterministicTwigIdCalculator,
        private FingerprintCalculator $fingerprintCalculator,
        private TwigAttributeHelper $twigAttributeHelper,
        private ComponentFactory $componentFactory,
        private LiveComponentHydrator $liveComponentHydrator,
    ) {
    }

    public function preComponentCreated(PreCreateForRenderEvent $event): void
    {
        // if there is already a component, that's a parent. Else, this is not a child.
        if (!$this->componentStack->getCurrentComponent()) {
            return;
        }

        $parentComponent = $this->componentStack->getCurrentComponent();
        if (!$parentComponent->hasExtraMetadata(self::CHILDREN_FINGERPRINTS_METADATA_KEY)) {
            return;
        }

        $childFingerprints = $parentComponent->getExtraMetadata(self::CHILDREN_FINGERPRINTS_METADATA_KEY);

        // get the deterministic id for this child, but without incrementing the counter yet
        $deterministicId = $this->deterministicTwigIdCalculator->calculateDeterministicId(increment: false);
        if (!isset($childFingerprints[$deterministicId])) {
            // child fingerprint wasn't set, it is likely a new child, allow it to render fully
            return;
        }

        // increment the internal counter now to keep "counter" consistency if we're
        // in a loop of children being rendered on the same line
        // we need to do this because this component will *not* ever hit
        // AddLiveAttributesSubscriber where the counter is normally incremented
        $this->deterministicTwigIdCalculator->calculateDeterministicId(increment: true);

        $newPropsFingerprint = $this->fingerprintCalculator->calculateFingerprint($event->getProps());

        if ($childFingerprints[$deterministicId] === $newPropsFingerprint) {
            // the props passed to create this child have *not* changed
            // return an empty element so the frontend knows to keep the current child

            $rendered = sprintf(
                '<div data-live-id="%s"></div>',
                $this->twigAttributeHelper->escapeAttribute($deterministicId)
            );
            $event->setRenderedString($rendered);

            return;
        }

        /*
         * The props passed to create this child HAVE changed.
         * Send back a fake element with:
         *      * data-live-id
         *      * data-live-fingerprint-value (new fingerprint)
         *      * data-live-props-value (new dehydrated props)
         */
        $mounted = $this->componentFactory->create($event->getName(), $event->getProps());
        $dehydratedComponent = $this->liveComponentHydrator->dehydrate($mounted);

        $rendered = sprintf(
            '<div data-live-id="%s" data-live-fingerprint-value="%s" data-live-props-value="%s"></div>',
            $this->twigAttributeHelper->escapeAttribute($deterministicId),
            $this->twigAttributeHelper->escapeAttribute($newPropsFingerprint),
            $this->twigAttributeHelper->escapeAttribute(json_encode($dehydratedComponent->getProps(), \JSON_THROW_ON_ERROR))
        );
        $event->setRenderedString($rendered);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreCreateForRenderEvent::class => 'preComponentCreated',
        ];
    }
}
