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

use Symfony\UX\LiveComponent\DehydratedComponent;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
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

    private function dehydrateComponent(MountedComponent $mounted): DehydratedComponent
    {
        return $this->hydrator()->dehydrate($mounted);
    }

    private function hydrateComponent(object $component, array $data, string $name): MountedComponent
    {
        return $this->hydrator()->hydrate($component, $data, $name);
    }
}
