<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\UX\TwigComponent\MountedComponent;

final class PostRenderEvent extends Event
{
    /**
     * @internal
     */
    public function __construct(
        private MountedComponent $mounted,
        private ?string $content = null,
    ) {
    }

    public function getMountedComponent(): MountedComponent
    {
        return $this->mounted;
    }

    /**
     * The rendered content of the component.
     *
     * (not available for the `embedded` components)
     *
     * @internal
     */
    public function getContent(): ?string
    {
        return $this->content;
    }
}
