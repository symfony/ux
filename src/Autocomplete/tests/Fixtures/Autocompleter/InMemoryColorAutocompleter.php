<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Fixtures\Autocompleter;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\Autocomplete\AutocompleteResults;
use Symfony\UX\Autocomplete\AutocompleterInterface;

/**
 * A direct AutocompleterInterface implementation (not backed by Doctrine or a form type).
 */
final class InMemoryColorAutocompleter implements AutocompleterInterface
{
    private const MAX_RESULTS = 5;

    private const COLORS = [
        ['text' => 'Red', 'value' => 'red'],
        ['text' => 'Green', 'value' => 'green'],
        ['text' => 'Blue', 'value' => 'blue'],
        ['text' => 'Yellow', 'value' => 'yellow'],
        ['text' => 'Purple', 'value' => 'purple'],
        ['text' => 'Orange', 'value' => 'orange'],
        ['text' => 'Pink', 'value' => 'pink'],
        ['text' => 'Brown', 'value' => 'brown'],
        ['text' => 'Black', 'value' => 'black'],
        ['text' => 'White', 'value' => 'white'],
        ['text' => 'Gray', 'value' => 'gray'],
        ['text' => 'Cyan', 'value' => 'cyan'],
    ];

    public function fetchResults(string $query, int $page): AutocompleteResults
    {
        $filtered = '' === $query
            ? self::COLORS
            : array_values(array_filter(self::COLORS, static fn (array $color) => str_contains(strtolower($color['text']), strtolower($query))));

        $offset = ($page - 1) * self::MAX_RESULTS;
        $pageResults = \array_slice($filtered, $offset, self::MAX_RESULTS);
        $hasNextPage = \count($filtered) > $offset + self::MAX_RESULTS;

        return new AutocompleteResults($pageResults, $hasNextPage);
    }

    public function isGranted(Security $security): bool
    {
        return true;
    }
}
