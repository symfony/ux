<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Form;

/**
 * All form types that want to expose autocomplete functionality should have this.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsEntityAutocompleteField extends AsAutocompleteField
{
}
