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
 * Trait with shortcut methods useful for live components.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
trait ComponentToolsTrait
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

    public function emit(string $eventName, array $data = [], ?string $componentName = null): void
    {
        $this->liveResponder->emit($eventName, $data, $componentName);
    }

    public function emitUp(string $eventName, array $data = [], ?string $componentName = null): void
    {
        $this->liveResponder->emitUp($eventName, $data, $componentName);
    }

    public function emitSelf(string $eventName, array $data = []): void
    {
        $this->liveResponder->emitSelf($eventName, $data);
    }

    public function dispatchBrowserEvent(string $eventName, array $payload = []): void
    {
        $this->liveResponder->dispatchBrowserEvent($eventName, $payload);
    }
}
