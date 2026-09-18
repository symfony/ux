<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Util;

use Symfony\UX\Icons\IconFinderInterface;

/**
 * @author Pierre du Plessis <pierre@pcservice.co.za>
 */
final class InMemoryIconFinder implements IconFinderInterface
{
    /**
     * @param list<string> $icons
     */
    public function __construct(
        private array $icons = [],
    ) {
    }

    public function icons(): iterable
    {
        return $this->icons;
    }
}
