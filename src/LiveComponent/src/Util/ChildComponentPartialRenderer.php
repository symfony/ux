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
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentFactory;
use Twig\Environment;
use Twig\Runtime\EscaperRuntime;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
class ChildComponentPartialRenderer implements ServiceSubscriberInterface
{
    public function __construct(
        private FingerprintCalculator $fingerprintCalculator,
        private TwigAttributeHelperFactory $attributeHelperFactory,
        private Environment $twig,
        private ContainerInterface $container,
    ) {
    }

    public function renderChildComponent(string $deterministicId, string $currentPropsFingerprint, string $childTag, string $componentName, array $inputProps): string
    {
        $liveMetadata = $this->getLiveComponentMetadataFactory()->getMetadata($componentName);
        $newPropsFingerprint = $this->fingerprintCalculator->calculateFingerprint($inputProps, $liveMetadata);

        if ($currentPropsFingerprint === $newPropsFingerprint) {
            // the props passed to create this child have *not* changed
            // return an empty element so the frontend knows to keep the current child

            $attributesCollection = $this->attributeHelperFactory->create();
            $attributesCollection->setLiveId($deterministicId);

            return $this->createHtml($attributesCollection->toArray(), $childTag);
        }

        /*
         * The props passed to create this child HAVE changed.
         * Send back a fake element with:
         *      * id
         *      * data-live-fingerprint-value (new fingerprint)
         *      * data-live-props-value (dehydrated props that "accept updates from parent")
         */
        $mounted = $this->getComponentFactory()->create($componentName, $inputProps);
        $attributesCollection = $this->getAttributesCreator()->attributesForRendering(
            $mounted,
            $this->getComponentFactory()->metadataFor($componentName),
            true,
            $deterministicId
        );

        $props = $attributesCollection->getProps();

        // only send back the props that are allowed to be updated from the parent
        $readonlyDehydratedProps = $liveMetadata->getOnlyPropsThatAcceptUpdatesFromParent($props);
        $readonlyDehydratedProps = $this->getLiveComponentHydrator()->addChecksumToData($readonlyDehydratedProps);

        $attributesCollection->setPropsUpdatedFromParent($readonlyDehydratedProps);
        $attributes = $attributesCollection->toArray();
        // optional, but these just aren't needed by the frontend at this point
        unset($attributes['data-controller']);
        unset($attributes['data-live-url-value']);
        unset($attributes['data-live-props-value']);

        return $this->createHtml($attributes, $childTag);
    }

    /**
     * @param array<string, string> $attributes
     */
    private function createHtml(array $attributes, string $childTag): string
    {
        $attributes['data-live-preserve'] = true;
        $attributes = new ComponentAttributes($attributes, $this->twig->getRuntime(EscaperRuntime::class));

        return \sprintf('<%s%s></%s>', $childTag, $attributes, $childTag);
    }

    public static function getSubscribedServices(): array
    {
        return [
            ComponentFactory::class,
            LiveComponentMetadataFactory::class,
            LiveComponentHydrator::class,
            LiveControllerAttributesCreator::class,
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

    private function getAttributesCreator(): LiveControllerAttributesCreator
    {
        return $this->container->get(LiveControllerAttributesCreator::class);
    }
}
