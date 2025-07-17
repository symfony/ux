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

class CustomGroupByProductAutocompleter extends CustomProductAutocompleter
{
    public function getGroupBy(): mixed
    {
        return 'category.name';
    }
}
