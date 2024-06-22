<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\LiveMemory\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * @demo   LiveMemory
 *
 * @author Simon André <smn.andre@gmail.com>
 */
#[AsLiveComponent(
    name: 'LiveMemory:Timer',
    template: 'demos/live_memory/components/LiveMemory/Timer.html.twig',
)]
class Timer
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public int $duration = 0;

    #[LiveProp(hydrateWith: 'hydrateDate', dehydrateWith: 'dehydrateDate', format: 'U')]
    public \DateTimeImmutable $startedAt;

    #[LiveProp]
    public bool $running = true;

    #[LiveProp]
    public bool $blinking = false;

    #[LiveProp]
    public int $remainingTime = 0;

    #[LiveProp]
    public int $warningThreshold = 10;

    #[PostMount]
    #[PostHydrate]
    public function refreshProps(): void
    {
        $this->remainingTime = $this->getRemainingTime();
        $this->blinking = $this->running && $this->isUnderWarningThreshold();
    }

    #[LiveAction]
    public function tick(): void
    {
        if (!$this->running) {
            return;
        }

        if ($this->getRemainingTime() <= 0) {
            $this->running = false;
            $this->emit('LiveMemory:Timer:TimeOut');

            return;
        }

        if (!$this->blinking && $this->isUnderWarningThreshold()) {
            $this->blinking = true;
        }
    }

    /**
     * Get the remaining time before timer expiration.
     *
     * @return int Remaining time (in ms)
     */
    public function getRemainingTime(): int
    {
        $expiresAt = $this->startedAt->modify(\sprintf('+%d seconds', $this->duration));
        $difference = max(0, $expiresAt->getTimestamp() - microtime(true));

        // Number of milliseconds until the timer expires
        return (int) ($difference * 1000);
    }

    public function isUnderWarningThreshold(): bool
    {
        if (!($remainingTime = $this->getRemainingTime())) {
            return false;
        }

        return $remainingTime <= ($this->warningThreshold * 1000);
    }

    public function hydrateDate(?string $startedAt): ?\DateTimeImmutable
    {
        if (null === $startedAt || '' === $startedAt) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('U', $startedAt);
    }

    public function dehydrateDate(?\DateTimeImmutable $startedAt): ?string
    {
        return (string) $startedAt?->format('U');
    }
}
