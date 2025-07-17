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
final class GameFactory
{
    public static function createGame(int $level = 1): Game
    {
        if (!\in_array($level, GameLevels::getLevels())) {
            throw new \InvalidArgumentException(\sprintf('Level "%s" does not exist.', $level));
        }

        [$nbCards, $theme, $timeLimit] = array_values(GameLevels::getLevelMetadata($level));
        $cards = GameCards::drawPairs($nbCards / 2);

        return new Game($level, $theme, $cards, $timeLimit);
    }

    public static function createNextGame(Game $game): Game
    {
        if (!$game->isEnded()) {
            throw new \InvalidArgumentException('Game must be ended to create the next one.');
        }

        // Cannot progress without winning: try again!
        if (!$game->isCompleted()) {
            return self::createGame($game->getLevel());
        }

        // Cannot progress beyond the last level: back to level 1
        if (!\in_array($game->getLevel() + 1, GameLevels::getLevels())) {
            return self::createGame();
        }

        return self::createGame($game->getLevel() + 1);
    }
}
