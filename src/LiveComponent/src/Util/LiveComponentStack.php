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

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\MountedComponent;

/**
 * This class decorates the TwigComponent\ComponentStack adding specific Live component functionalities.
 *
 * @author Bart Vanderstukken <bart.vanderstukken@gmail.com>
 *
 * @internal
 */
final class LiveComponentStack extends ComponentStack
{
    public function __construct(private readonly ComponentStack $componentStack)
    {
    }

    public function getCurrentLiveComponent(): ?MountedComponent
    {
        foreach ($this->componentStack as $mountedComponent) {
            if ($this->isLiveComponent($mountedComponent->getComponent()::class)) {
                return $mountedComponent;
            }
        }

        return null;
    }

    private function isLiveComponent(string $classname): bool
    {
        return [] !== (new \ReflectionClass($classname))->getAttributes(AsLiveComponent::class);
    }

    public function getCurrentComponent(): ?MountedComponent
    {
        return $this->componentStack->getCurrentComponent();
    }

    public function getParentComponent(): ?MountedComponent
    {
        return $this->componentStack->getParentComponent();
    }

    public function hasParentComponent(): bool
    {
        return $this->componentStack->hasParentComponent();
    }
}
