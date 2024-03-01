<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Intl;

/**
 * Adapted from https://github.com/formatjs/formatjs/blob/590f1f81b26934c6dc7a55fff938df5436c6f158/packages/icu-messageformat-parser/types.ts#L8-L46.
 *
 * @experimental
 */
final class Type
{
    /**
     * Raw text.
     */
    public const LITERAL = 'literal';

    /**
     * Variable w/o any format, e.g `var` in `this is a {var}`.
     */
    public const ARGUMENT = 'argument';

    /**
     * Variable w/ number format.
     */
    public const NUMBER = 'number';

    /**
     * Variable w/ date format.
     */
    public const DATE = 'date';

    /**
     * Variable w/ time format.
     */
    public const TIME = 'time';

    /**
     * Variable w/ select format.
     */
    public const SELECT = 'select';

    /**
     * Variable w/ plural format.
     */
    public const PLURAL = 'plural';

    /**
     * Only possible within plural argument.
     * This is the `#` symbol that will be substituted with the count.
     */
    public const POUND = 'pound';

    /**
     * XML-like tag.
     */
    public const TAG = 'tag';

    private function __construct()
    {
    }
}
