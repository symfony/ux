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

/**
 * @demo   LiveMemory
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class GameEngine
{
    public function flipCard(Game $game, int $cardKey): void
    {
        if (!$game->isPlaying()) {
            return;
        }
        // Cannot flip card already matched
        if (\in_array($cardKey, $game->getMatches())) {
            return;
        }
        // Cannot flip card already flipped
        if ([$cardKey] === $game->getCurrentPair()) {
            return;
        }
        // Flip Card
        $game->addFlip($cardKey);

        $this->checkPair($game);

        if ($game->isCompleted()) {
            $this->endGame($game);
        }
    }

    public function checkTime(Game $game): void
    {
        if (!$game->isPlaying()) {
            return;
        }
        // Game is not over yet
        if ($game->getExpiredAt()->getTimestamp() > (new \DateTimeImmutable('now'))->getTimestamp()) {
            return;
        }

        $this->endGame($game, $game->getExpiredAt());
    }

    private function checkPair(Game $game): void
    {
        if (2 !== \count($game->getCurrentPair())) {
            return;
        }
        [$first, $second] = $game->getCurrentPair();
        if ($game->getCard($first) !== $game->getCard($second)) {
            return;
        }

        $game->addMatch($first);
        $game->addMatch($second);

        $pointPerMatch = 50;
        $game->setScore($game->getScore() + ($game->getCardCount() * $pointPerMatch));
    }

    private function endGame(Game $game, \DateTimeImmutable $endedAt = null): void
    {
        $game->setEndedAt($endedAt ?? new \DateTimeImmutable('now'));
        if ($game->isCompleted()) {
            $bonusPerMove = 50;
            $game->setMoveBonus(max(0, (3 * $game->getCardCount() - $game->getFlipCount()) * $bonusPerMove));

            $bonusPerSecond = 25;
            $game->setTimeBonus(max(0, (2 * $game->getTimeLimit() - $game->getTime()) * $bonusPerSecond));
        }
    }
}
