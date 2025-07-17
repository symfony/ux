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

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class LiveResponder
{
    /**
     * Each item is an array with keys: event, data, target, componentName.
     */
    private array $eventsToEmit = [];

    /**
     * Each item is an array with keys: event, payload.
     */
    private array $browserEventsToDispatch = [];

    public function emit(string $eventName, array $data = [], ?string $componentName = null): void
    {
        $this->eventsToEmit[] = [
            'event' => $eventName,
            'data' => $data,
            'target' => null,
            'componentName' => $componentName,
        ];
    }

    public function emitUp(string $eventName, array $data = [], ?string $componentName = null): void
    {
        $this->eventsToEmit[] = [
            'event' => $eventName,
            'data' => $data,
            'target' => 'up',
            'componentName' => $componentName,
        ];
    }

    public function emitSelf(string $eventName, array $data = []): void
    {
        $this->eventsToEmit[] = [
            'event' => $eventName,
            'data' => $data,
            'target' => 'self',
            'componentName' => null,
        ];
    }

    public function dispatchBrowserEvent(string $event, array $payload = []): void
    {
        $this->browserEventsToDispatch[] = [
            'event' => $event,
            'payload' => $payload,
        ];
    }

    public function getEventsToEmit(): array
    {
        return $this->eventsToEmit;
    }

    public function getBrowserEventsToDispatch(): array
    {
        return $this->browserEventsToDispatch;
    }

    public function reset(): void
    {
        $this->eventsToEmit = [];
        $this->browserEventsToDispatch = [];
    }
}
