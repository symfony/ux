<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests;

use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\MountedComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait LiveComponentTestHelper
{
    private function hydrator(): LiveComponentHydrator
    {
        return self::getContainer()->get('ux.live_component.component_hydrator');
    }

    private function factory(): ComponentFactory
    {
        return self::getContainer()->get('ux.twig_component.component_factory');
    }

    private function getComponent(string $name): object
    {
        return $this->factory()->get($name);
    }

    private function mountComponent(string $name, array $data = []): MountedComponent
    {
        return $this->factory()->create($name, $data);
    }

    private function dehydrateComponent(MountedComponent $mounted): array
    {
        $liveMetadataFactory = self::getContainer()->get('ux.live_component.metadata_factory');
        \assert($liveMetadataFactory instanceof LiveComponentMetadataFactory);

        return $this->hydrator()->dehydrate(
            $mounted->getComponent(),
            $mounted->getAttributes(),
            $liveMetadataFactory->getMetadata($mounted->getName())
        );
    }

    private function hydrateComponent(object $component, string $name, array $props, array $updatedProps = []): ComponentAttributes
    {
        $liveMetadataFactory = self::getContainer()->get('ux.live_component.metadata_factory');
        \assert($liveMetadataFactory instanceof LiveComponentMetadataFactory);

        return $this->hydrator()->hydrate($component, $props, $updatedProps, $liveMetadataFactory->getMetadata($name));
    }
}
