<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\Finder;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Icons\Finder\ChainIconFinder;
use Symfony\UX\Icons\Tests\Util\InMemoryIconFinder;

final class ChainIconFinderTest extends TestCase
{
    public function testMergesIconsFromAllFinders(): void
    {
        $finder = new ChainIconFinder([
            new InMemoryIconFinder(['lucide:circle', 'flag:eu-4x3']),
            new InMemoryIconFinder(['tabler:mail']),
        ]);

        $this->assertSame(['lucide:circle', 'flag:eu-4x3', 'tabler:mail'], iterator_to_array($finder->icons(), false));
    }

    public function testDuplicateIconsAreReturnedOnce(): void
    {
        $finder = new ChainIconFinder([
            new InMemoryIconFinder(['lucide:circle', 'tabler:mail']),
            new InMemoryIconFinder(['tabler:mail']),
        ]);

        $this->assertSame(['lucide:circle', 'tabler:mail'], iterator_to_array($finder->icons(), false));
    }

    public function testNoFinders(): void
    {
        $this->assertSame([], iterator_to_array(new ChainIconFinder([])->icons(), false));
    }
}
