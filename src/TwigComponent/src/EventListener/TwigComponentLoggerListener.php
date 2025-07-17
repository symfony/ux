<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\TwigComponent\Event\PostRenderEvent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class TwigComponentLoggerListener implements EventSubscriberInterface, ResetInterface
{
    private array $events = [];

    public static function getSubscribedEvents(): array
    {
        return [
            PreRenderEvent::class => ['onPreRender', 255],
            PostRenderEvent::class => ['onPostRender', -255],
        ];
    }

    /**
     * @return list<array{
     *     PreRenderEvent|PostRenderEvent,
     *     array{float, int},
     * }>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function onPreRender(PreRenderEvent $event): void
    {
        $this->logEvent($event);
    }

    public function onPostRender(PostRenderEvent $event): void
    {
        $this->logEvent($event);
    }

    public function reset(): void
    {
        $this->events = [];
    }

    private function logEvent(object $event): void
    {
        $this->events[] = [$event, [microtime(true), memory_get_usage(true)]];
    }
}
