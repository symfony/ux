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
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\TwigComponent\Event\PostMountEvent;
use Symfony\UX\TwigComponent\Event\PostRenderEvent;
use Symfony\UX\TwigComponent\Event\PreCreateForRenderEvent;
use Symfony\UX\TwigComponent\Event\PreMountEvent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class TwigComponentLoggerListener implements EventSubscriberInterface, ResetInterface
{
    private array $events = [];

    public function __construct(private ?Stopwatch $stopwatch = null)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreCreateForRenderEvent::class => [
                // High priority: start the stopwatch as soon as possible
                ['onPreCreateForRender', 255],
                // Low priority: check `event::getRenderedString()` as late as possible
                ['onPostCreateForRender', -255],
            ],
            PreMountEvent::class => ['onPreMount', 255],
            PostMountEvent::class => ['onPostMount', -255],
            PreRenderEvent::class => ['onPreRender', 255],
            PostRenderEvent::class => ['onPostRender', -255],
        ];
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function onPreCreateForRender(PreCreateForRenderEvent $event): void
    {
        $this->stopwatch?->start($event->getName(), 'twig_component');
        $this->logEvent($event);
    }

    private function logEvent(object $event): void
    {
        $this->events[] = [$event, [microtime(true), memory_get_usage(true)]];
    }

    public function onPostCreateForRender(PreCreateForRenderEvent $event): void
    {
        if (\is_string($event->getRenderedString())) {
            $this->stopwatch?->stop($event->getName());
            $this->logEvent($event);
        }
    }

    public function onPreMount(PreMountEvent $event): void
    {
        $this->logEvent($event);
    }

    public function onPostMount(PostMountEvent $event): void
    {
        $this->logEvent($event);
    }

    public function onPreRender(PreRenderEvent $event): void
    {
        $this->logEvent($event);
    }

    public function onPostRender(PostRenderEvent $event): void
    {
        if ($this->stopwatch?->isStarted($name = $event->getMountedComponent()->getName())) {
            $this->stopwatch->stop($name);
        }
        $this->logEvent($event);
    }

    public function reset(): void
    {
        $this->events = [];
    }
}
