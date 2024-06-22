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
final class GameCards
{
    /**
     * @return list<string>
     */
    public static function getCards(): array
    {
        return array_map(fn ($i) => \sprintf('%02d', $i), range(1, 16));
    }

    /**
     * @return list<string>
     */
    public static function drawPairs(int $nbPairs): array
    {
        $cards = self::drawCards($nbPairs);
        $cards = [...$cards, ...$cards];
        shuffle($cards);

        return array_values($cards);
    }

    /**
     * @return list<string>
     */
    public static function drawCards(int $nbCards): array
    {
        if ($nbCards < 0) {
            throw new \InvalidArgumentException('Cannot draw a negative number of cards.');
        }
        $cards = [...self::getCards()];
        if ($nbCards > \count($cards)) {
            throw new \InvalidArgumentException('Cannot draw more cards than the deck contains.');
        }

        shuffle($cards);

        return \array_slice($cards, 0, $nbCards);
    }
}
