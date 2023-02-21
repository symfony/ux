<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Util;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\TwigComponent\ComponentFactory;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 *
 * @internal
 */
class ChildComponentPartialRenderer implements ServiceSubscriberInterface
{
    public function __construct(
        private FingerprintCalculator $fingerprintCalculator,
        private TwigAttributeHelper $attributeHelper,
        private ContainerInterface $container,
    ) {
    }

    public function renderChildComponent(string $deterministicId, string $currentPropsFingerprint, string $componentName, array $inputProps): string
    {
        $newPropsFingerprint = $this->fingerprintCalculator->calculateFingerprint($inputProps);

        if ($currentPropsFingerprint === $newPropsFingerprint) {
            // the props passed to create this child have *not* changed
            // return an empty element so the frontend knows to keep the current child

            $attributes = [];
            $this->attributeHelper->addLiveId($deterministicId, $attributes);

            return $this->createHtml($attributes);
        }

        /*
         * The props passed to create this child HAVE changed.
         * Send back a fake element with:
         *      * data-live-id
         *      * data-live-fingerprint-value (new fingerprint)
         *      * data-live-props-value (new READONLY dehydrated props)
         */
        $mounted = $this->getComponentFactory()->create($componentName, $inputProps);
        $liveMetadata = $this->getLiveComponentMetadataFactory()->getMetadata($mounted->getName());
        $dehydratedProps = $this->getLiveComponentHydrator()->dehydrate(
            $mounted->getComponent(),
            $mounted->getAttributes(),
            $liveMetadata
        );

        // only send back the props that are marked as "read-only"
        $readonlyDehydratedProps = array_intersect_key(
            $dehydratedProps,
            array_flip(array_merge($liveMetadata->getReadonlyPropPaths(), LiveComponentHydrator::getInternalPropNames()))
        );

        $attributes = [];
        $this->attributeHelper->addLiveId($deterministicId, $attributes);
        $this->attributeHelper->addFingerprint($newPropsFingerprint, $attributes);
        $this->attributeHelper->addProps($readonlyDehydratedProps, $attributes);

        return $this->createHtml($attributes);
    }

    /**
     * @param array<string, string> $attributes
     */
    private function createHtml(array $attributes): string
    {
        // transform attributes into an array of key="value" strings
        $attributes = array_map(function ($key, $value) {
            return sprintf('%s="%s"', $key, $value);
        }, array_keys($attributes), $attributes);

        return sprintf('<div %s></div>', implode(' ', $attributes));
    }

    public static function getSubscribedServices(): array
    {
        return [
            ComponentFactory::class,
            LiveComponentMetadataFactory::class,
            LiveComponentHydrator::class,
        ];
    }

    private function getComponentFactory(): ComponentFactory
    {
        return $this->container->get(ComponentFactory::class);
    }

    private function getLiveComponentMetadataFactory(): LiveComponentMetadataFactory
    {
        return $this->container->get(LiveComponentMetadataFactory::class);
    }

    private function getLiveComponentHydrator(): LiveComponentHydrator
    {
        return $this->container->get(LiveComponentHydrator::class);
    }
}
