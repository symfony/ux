<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\LiveMemory;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

/**
 * @demo LiveMemory
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class Game
{
    private readonly string $id;

    private readonly int $level;

    private readonly string $theme;

    private readonly int $timeLimit;

    /**
     * Map of game card as <cardKey> => <cardValue>.
     *
     * @var list<int, string>
     */
    private readonly array $cards;

    /**
     * @var array<int>
     */
    private array $matches = [];

    /**
     * @var list<int>
     */
    private array $flips = [];

    private int $score = 0;

    private int $timeBonus = 0;

    private int $moveBonus = 0;

    private ?\DateTimeImmutable $startedAt = null;

    private ?\DateTimeImmutable $endedAt = null;

    /**
     * @param list<string> $cards List of card values
     */
    public function __construct(int $level, string $theme, array $cards, int $timeLimit)
    {
        $this->id = Uuid::v7()->toBase58();
        $this->level = $level;
        $this->theme = $theme;
        $this->cards = $cards;
        $this->timeLimit = $timeLimit;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    /**
     * @return list<string>
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    public function getCardCount(): int
    {
        return \count($this->cards);
    }

    public function getGrid(): array
    {
        $cardCount = $this->getCardCount();

        return [
            max(4, $cardCount / 4),
            min(4, $cardCount / 4),
        ];
    }

    public function getFlips(): array
    {
        return $this->flips;
    }

    public function getFlipCount(): int
    {
        return \count($this->flips);
    }

    public function addFlip(int $key): void
    {
        if (!isset($this->cards[$key])) {
            throw new \InvalidArgumentException(sprintf('Invalid card key "%s".', $key));
        }
        $this->flips[] = $key;
    }

    public function getMatches(): array
    {
        return $this->matches;
    }

    public function getMatchCount(): int
    {
        return \count($this->matches);
    }

    public function addMatch(int $key): void
    {
        if (!isset($this->cards[$key])) {
            throw new \InvalidArgumentException(sprintf('Invalid card key "%s".', $key));
        }
        if (\in_array($key, $this->matches)) {
            throw new \InvalidArgumentException(sprintf('Card "%s" is already matched.', $key));
        }
        $this->matches[] = $key;
    }

    public function getCurrentPair(): array
    {
        $selectedPairs = $this->getSelectedPairs();

        return array_pop($selectedPairs) ?? [];
    }

    public function getSelectedPairs(): array
    {
        return array_chunk($this->getFlips(), 2);
    }

    public function getCard(int $key): string
    {
        if (!isset($this->cards[$key])) {
            throw new \InvalidArgumentException(sprintf('Invalid card key "%s".', $key));
        }

        return $this->cards[$key];
    }

    public function geTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function isStarted(): bool
    {
        return null !== $this->startedAt;
    }

    public function isEnded(): bool
    {
        return null !== $this->endedAt;
    }

    public function isPlaying(): bool
    {
        return $this->isStarted() && !$this->isEnded();
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): void
    {
        $this->score = $score;
    }

    public function getTimeBonus(): int
    {
        return $this->timeBonus;
    }

    public function setTimeBonus(int $timeBonus): void
    {
        $this->timeBonus = $timeBonus;
    }

    public function getMoveBonus(): int
    {
        return $this->moveBonus;
    }

    public function setMoveBonus(int $moveBonus): void
    {
        $this->moveBonus = $moveBonus;
    }

    public function getTotalScore(): int
    {
        return $this->score + $this->moveBonus + $this->timeBonus;
    }

    public function getTime(): int
    {
        if (null !== $start = $this->getCreatedAt()) {
            $end = ($this->getEndedAt() ?? new \DateTimeImmutable('now'));

            return $end->getTimestamp() - $start->getTimestamp();
        }

        return 5;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return UuidV7::fromBase58($this->id)->getDateTime();
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getExpiredAt(): ?\DateTimeImmutable
    {
        return $this->startedAt?->modify(sprintf('+%d seconds', $this->timeLimit));
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(\DateTimeImmutable $endedAt): void
    {
        $this->endedAt = $endedAt;
    }

    public function isCompleted(): bool
    {
        return $this->getMatchCount() === $this->getCardCount();
    }
}
