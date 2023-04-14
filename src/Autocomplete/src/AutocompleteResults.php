<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete;

final class AutocompleteResults
{
    /**
     * @param list<array{text: string, value: mixed}> $results
     */
    public function __construct(
        public array $results,
        public bool $hasNextPage,
        public array $optgroups = [],
    ) {
    }
}
