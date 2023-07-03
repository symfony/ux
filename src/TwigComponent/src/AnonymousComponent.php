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
 * @author Matheo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 */
class AnonymousComponent
{
    private array $props = [];

    public function setProps(array $props)
    {
        $this->props = $props;
    }

    public function getProps(): array
    {
        return $this->props;
    }

    public function mount(array $props = [])
    {
        $this->setProps($props);
    }
}
