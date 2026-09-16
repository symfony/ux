<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Finder;

use Symfony\UX\Icons\IconFinderInterface;

/**
 * @author Pierre du Plessis <pierre@pcservice.co.za>
 *
 * @internal
 */
final class ChainIconFinder implements IconFinderInterface
{
    /**
     * @param iterable<IconFinderInterface> $finders
     */
    public function __construct(
        private iterable $finders,
    ) {
    }

    public function icons(): iterable
    {
        $seen = [];

        foreach ($this->finders as $finder) {
            foreach ($finder->icons() as $icon) {
                if (!isset($seen[$icon])) {
                    $seen[$icon] = true;

                    yield $icon;
                }
            }
        }
    }
}
