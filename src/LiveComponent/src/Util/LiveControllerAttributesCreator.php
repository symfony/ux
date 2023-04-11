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

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\LiveResponder;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Twig\DeterministicTwigIdCalculator;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\MountedComponent;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 *
 * @internal
 */
class LiveControllerAttributesCreator
{
    /**
     * Prop name that can be passed into a component to keep it unique in a loop.
     *
     * This is used to generate the unique data-live-id for the child component.
     */
    public const KEY_PROP_NAME = 'key';

    public function __construct(
        private LiveComponentMetadataFactory $metadataFactory,
        private LiveComponentHydrator $hydrator,
        private TwigAttributeHelperFactory $attributeHelper,
        private DeterministicTwigIdCalculator $idCalculator,
        private FingerprintCalculator $fingerprintCalculator,
        private UrlGeneratorInterface $urlGenerator,
        private LiveResponder $liveResponder,
        private ?CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    /**
     * Calculates the array of extra attributes that should be added to the root
     * component element to activate the live controller functionality.
     */
    public function attributesForRendering(MountedComponent $mounted, ComponentMetadata $metadata, bool $isChildComponent, string $deterministicId = null): LiveAttributesCollection
    {
        $attributesCollection = $this->attributeHelper->create();
        $attributesCollection->setLiveController($mounted->getName());

        $url = $this->urlGenerator->generate($metadata->get('route'), ['_live_component' => $mounted->getName()]);
        $attributesCollection->setUrl($url);

        $liveListeners = AsLiveComponent::liveListeners($mounted->getComponent());
        if ($liveListeners) {
            $attributesCollection->setListeners($liveListeners);
        }

        $eventsToEmit = $this->liveResponder->getEventsToEmit();
        $this->liveResponder->reset();
        if ($eventsToEmit) {
            $attributesCollection->setEventsToEmit($eventsToEmit);
        }

        $mountedAttributes = $mounted->getAttributes();

        if ($isChildComponent) {
            if (!isset($mountedAttributes->all()['data-live-id'])) {
                $id = $deterministicId ?: $this->idCalculator
                    ->calculateDeterministicId(key: $mounted->getInputProps()[self::KEY_PROP_NAME] ?? null);
                $attributesCollection->setLiveId($id);
                // we need to add this to the mounted attributes so that it is
                // will be included in the "attributes" part of the props data.
                $mountedAttributes = $mountedAttributes->defaults(['data-live-id' => $id]);
            }

            $fingerprint = $this->fingerprintCalculator->calculateFingerprint(
                $mounted->getInputProps(),
                $this->metadataFactory->getMetadata($mounted->getName())
            );
            if ($fingerprint) {
                $attributesCollection->setFingerprint($fingerprint);
            }
        }

        $dehydratedProps = $this->dehydrateComponent(
            $mounted->getName(),
            $mounted->getComponent(),
            $mountedAttributes
        );
        $attributesCollection->setProps($dehydratedProps->getProps());

        if ($this->csrfTokenManager && $metadata->get('csrf')) {
            $attributesCollection->setCsrf(
                $this->csrfTokenManager->getToken(self::getCsrfTokeName($mounted->getName()))->getValue(),
            );
        }

        return $attributesCollection;
    }

    public static function getCsrfTokeName(string $componentName): string
    {
        return 'live_component_'.$componentName;
    }

    private function dehydrateComponent(string $name, object $component, ComponentAttributes $attributes): DehydratedProps
    {
        $liveMetadata = $this->metadataFactory->getMetadata($name);

        return $this->hydrator->dehydrate(
            $component,
            $attributes,
            $liveMetadata
        );
    }
}
