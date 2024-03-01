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
 * Interface for classes that will have an "autocomplete" endpoint exposed with a possibility to pass additional form options.
 */
interface OptionsAwareEntityAutocompleterInterface extends EntityAutocompleterInterface
{
    public function setOptions(array $options): void;
}
