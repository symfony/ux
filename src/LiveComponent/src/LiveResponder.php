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
 * @experimental
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class LiveResponder
{
    /**
     * Key is the event name, value is an array with keys: event, data, target.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $eventsToEmit = [];

    public function emit(string $eventName, array $data = [], string $componentName = null): void
    {
        $this->eventsToEmit[] = [
            'event' => $eventName,
            'data' => $data,
            'target' => null,
            'componentName' => $componentName,
        ];
    }

    public function emitUp(string $eventName, array $data = [], string $componentName = null): void
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

    public function getEventsToEmit(): array
    {
        return $this->eventsToEmit;
    }

    public function reset(): void
    {
        $this->eventsToEmit = [];
    }
}
