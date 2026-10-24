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

/**
 * Interface for autocompleters that can exclude some values (e.g. the already
 * selected ones) from their results, before paginating them.
 */
interface ExcludedValuesAwareAutocompleterInterface
{
    /**
     * @param list<string> $values The values to exclude, as returned in the "value" key of the results
     */
    public function setExcludedValues(array $values): void;
}
