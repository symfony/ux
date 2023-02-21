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
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Twig\DeterministicTwigIdCalculator;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\ComponentStack;
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
    public function __construct(
        private LiveComponentMetadataFactory $metadataFactory,
        private LiveComponentHydrator $hydrator,
        private TwigAttributeHelper $attributeHelper,
        private ComponentStack $componentStack,
        private DeterministicTwigIdCalculator $idCalculator,
        private FingerprintCalculator $fingerprintCalculator,
        private UrlGeneratorInterface $urlGenerator,
        private ?CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function attributesForRendering(MountedComponent $mounted, ComponentMetadata $metadata): array
    {
        $attributes = [];
        $this->attributeHelper->addLiveController($attributes);

        $url = $this->urlGenerator->generate($metadata->get('route'), ['_live_component' => $mounted->getName()]);
        $this->attributeHelper->addLiveUrl($url, $attributes);

        $dehydratedProps = $this->dehydrateComponent($mounted);
        $this->attributeHelper->addProps($dehydratedProps, $attributes);

        if ($this->csrfTokenManager && $metadata->get('csrf')) {
            $this->attributeHelper->addLiveCsrf(
                $this->csrfTokenManager->getToken(self::getCsrfTokeName($mounted->getName()))->getValue(),
                $attributes
            );
        }

        if ($this->componentStack->hasParentComponent()) {
            if (!isset($mounted->getAttributes()->all()['data-live-id'])) {
                $id = $this->idCalculator->calculateDeterministicId();
                $this->attributeHelper->addLiveId($id, $attributes);
            }

            $fingerprint = $this->fingerprintCalculator->calculateFingerprint($mounted->getInputProps());
            $this->attributeHelper->addFingerprint($fingerprint, $attributes);
        }

        return $attributes;
    }

    public static function getCsrfTokeName(string $componentName): string
    {
        return 'live_component_'.$componentName;
    }

    private function dehydrateComponent(MountedComponent $mounted): array
    {
        $liveMetadata = $this->metadataFactory->getMetadata($mounted->getName());

        return $this->hydrator->dehydrate(
            $mounted->getComponent(),
            $mounted->getAttributes(),
            $liveMetadata
        );
    }
}
