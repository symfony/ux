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
 * @demo LiveMemory
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class GameLevels
{
    /**
     * Level metadata:
     *  nbPairs = (level + 2)
     *  nbCards = (level + 2) * 2
     *  timeLimit = level * 20
     *
     * @const array<int, array{nbCards: int, theme: string, timeLimit: int, grid: string}>
     */
    private const LEVEL_METADATA = [
        1 => [6, 'blue', 20, '3x2'],
        2 => [12, 'green', 40, '4x3'],
        3 => [20, 'orange', 60, '5x4'],
    ];

    /**
     * @return list<int>
     */
    public static function getLevels(): array
    {
        return array_keys(self::LEVEL_METADATA);
    }

    /**
     * @return array{nbCards: int, theme: string, timeLimit: int, grid: string}
     */
    public static function getLevelMetadata(int $level = 1): array
    {
        if (!isset(self::LEVEL_METADATA[$level])) {
            throw new \InvalidArgumentException(\sprintf('Level "%s" does not exist.', $level));
        }

        return array_combine(['nbCards', 'theme', 'timeLimit', 'grid'], self::LEVEL_METADATA[$level]);
    }
}
