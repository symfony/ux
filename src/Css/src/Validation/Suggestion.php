<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Validation;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Suggestion
{
    /**
     * @param iterable<string> $candidates
     *
     * @return string " Did you mean "…"?" with the closest candidate, or an empty string when none is close enough
     */
    public static function didYouMean(string $name, iterable $candidates): string
    {
        $best = null;
        $bestDistance = \PHP_INT_MAX;
        foreach ($candidates as $candidate) {
            $distance = levenshtein($name, $candidate);
            if (
                $distance < $bestDistance
                && ($distance <= max(2, \strlen($name) / 3) || str_contains($candidate, $name))
            ) {
                $best = $candidate;
                $bestDistance = $distance;
            }
        }

        return null === $best ? '' : \sprintf(' Did you mean "%s"?', $best);
    }
}
