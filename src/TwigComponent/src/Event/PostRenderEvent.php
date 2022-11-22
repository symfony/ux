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
    public function __construct(private MountedComponent $mounted)
    {
    }

    public function getMountedComponent(): MountedComponent
    {
        return $this->mounted;
    }
}
