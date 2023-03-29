<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent;

use Symfony\Contracts\Service\Attribute\Required;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 */
trait ComponentEmitsTrait
{
    private LiveResponder $liveResponder;

    /**
     * @internal
     */
    #[Required]
    public function setLiveResponder(LiveResponder $liveResponder): void
    {
        $this->liveResponder = $liveResponder;
    }

    public function emit(string $eventName, array $data = [], string $componentName = null): void
    {
        $this->liveResponder->emit($eventName, $data, $componentName);
    }

    public function emitUp(string $eventName, array $data = [], string $componentName = null): void
    {
        $this->liveResponder->emitUp($eventName, $data, $componentName);
    }

    public function emitSelf(string $eventName, array $data = []): void
    {
        $this->liveResponder->emitSelf($eventName, $data);
    }
}
