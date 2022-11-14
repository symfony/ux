<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

/**
 * @internal
 */
final class RenderableComponent
{
    public function __construct(
        private ComponentRenderer $componentRenderer,
        private MountedComponent $component,
    ) {
    }

    public function withProps(array $props): self
    {
//        $r = new \ReflectionObject($this->component);
//        $inputProps = $r->getProperty('inputProps');
//        $inputProps->setValue($this->component, array_merge($inputProps->getValue($this->component), $props));

        $this->component->withProps($props);
        return $this;
    }

    public function __toString(): string
    {
        return $this->componentRenderer->render($this->component);
    }
}
