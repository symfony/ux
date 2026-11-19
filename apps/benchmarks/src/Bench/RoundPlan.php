<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Bench;

final class RoundPlan
{
    /**
     * @param list<Target> $targets
     * @param list<Side>   $sides
     *
     * @return list<array{Target, Side}>
     */
    public static function order(array $targets, array $sides, int $round): array
    {
        $orderedTargets = 0 === $round % 2 ? array_reverse($targets) : $targets;
        $offset = ($round - 1) % \count($sides);
        $orderedSides = [...\array_slice($sides, $offset), ...\array_slice($sides, 0, $offset)];

        $plan = [];
        foreach ($orderedTargets as $target) {
            foreach ($orderedSides as $side) {
                $plan[] = [$target, $side];
            }
        }

        return $plan;
    }
}
