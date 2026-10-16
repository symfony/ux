<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Fixtures\EventListener;

use Symfony\UX\TwigComponent\Event\PostMountEvent;
use Symfony\UX\TwigComponent\Event\PostRenderEvent;
use Symfony\UX\TwigComponent\Event\PreCreateForRenderEvent;
use Symfony\UX\TwigComponent\Event\PreMountEvent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

final class ComponentEventRecorder
{
    /**
     * @var list<array{class-string, string}>
     */
    public array $events = [];

    public function __invoke(object $event): void
    {
        $this->events[] = [$event::class, match (true) {
            $event instanceof PreCreateForRenderEvent => $event->getName(),
            $event instanceof PreMountEvent, $event instanceof PostMountEvent, $event instanceof PreRenderEvent => $event->getMetadata()?->getName(),
            $event instanceof PostRenderEvent => $event->getMountedComponent()->getName(),
        }];
    }
}
