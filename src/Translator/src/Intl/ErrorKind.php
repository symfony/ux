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
 * Adapted from https://github.com/formatjs/formatjs/blob/590f1f81b26934c6dc7a55fff938df5436c6f158/packages/icu-messageformat-parser/error.ts#L9-L77.
 *
 * @experimental
 */
final class ErrorKind
{
    /** Argument is unclosed (e.g. `{0`) */
    public const EXPECT_ARGUMENT_CLOSING_BRACE = 'EXPECT_ARGUMENT_CLOSING_BRACE';
    /** Argument is empty (e.g. `{}`). */
    public const EMPTY_ARGUMENT = 'EMPTY_ARGUMENT';
    /** Argument is malformed (e.g. `{foo!}``) */
    public const MALFORMED_ARGUMENT = 'MALFORMED_ARGUMENT';
    /** Expect an argument type (e.g. `{foo,}`) */
    public const EXPECT_ARGUMENT_TYPE = 'EXPECT_ARGUMENT_TYPE';
    /** Unsupported argument type (e.g. `{foo,foo}`) */
    public const INVALID_ARGUMENT_TYPE = 'INVALID_ARGUMENT_TYPE';
    /** Expect an argument style (e.g. `{foo, number, }`) */
    public const EXPECT_ARGUMENT_STYLE = 'EXPECT_ARGUMENT_STYLE';
    /** The number skeleton is invalid. */
    public const INVALID_NUMBER_SKELETON = 'INVALID_NUMBER_SKELETON';
    /** The date time skeleton is invalid. */
    public const INVALID_DATE_TIME_SKELETON = 'INVALID_DATE_TIME_SKELETON';
    /** Exepct a number skeleton following the `::` (e.g. `{foo, number, ::}`) */
    public const EXPECT_NUMBER_SKELETON = 'EXPECT_NUMBER_SKELETON';
    /** Exepct a date time skeleton following the `::` (e.g. `{foo, date, ::}`) */
    public const EXPECT_DATE_TIME_SKELETON = 'EXPECT_DATE_TIME_SKELETON';

    /** Unmatched apostrophes in the argument style (e.g. `{foo, number, 'test`) */
    public const UNCLOSED_QUOTE_IN_ARGUMENT_STYLE = 'UNCLOSED_QUOTE_IN_ARGUMENT_STYLE';

    /** Missing select argument options (e.g. `{foo, select}`) */
    public const EXPECT_SELECT_ARGUMENT_OPTIONS = 'EXPECT_SELECT_ARGUMENT_OPTIONS';

    /** Expecting an offset value in `plural` or `selectordinal` argument (e.g `{foo, plural, offset}`) */
    public const EXPECT_PLURAL_ARGUMENT_OFFSET_VALUE = 'EXPECT_PLURAL_ARGUMENT_OFFSET_VALUE';

    /** Offset value in `plural` or `selectordinal` is invalid (e.g. `{foo, plural, offset: x}`) */
    public const INVALID_PLURAL_ARGUMENT_OFFSET_VALUE = 'INVALID_PLURAL_ARGUMENT_OFFSET_VALUE';

    /** Expecting a selector in `select` argument (e.g `{foo, select}`) */
    public const EXPECT_SELECT_ARGUMENT_SELECTOR = 'EXPECT_SELECT_ARGUMENT_SELECTOR';

    /** Expecting a selector in `plural` or `selectordinal` argument (e.g `{foo, plural}`) */
    public const EXPECT_PLURAL_ARGUMENT_SELECTOR = 'EXPECT_PLURAL_ARGUMENT_SELECTOR';

    /** Expecting a message fragment after the `select` selector (e.g. `{foo, select, apple}`) */
    public const EXPECT_SELECT_ARGUMENT_SELECTOR_FRAGMENT = 'EXPECT_SELECT_ARGUMENT_SELECTOR_FRAGMENT';

    /**
     * Expecting a message fragment after the `plural` or `selectordinal` selector
     * (e.g. `{foo, plural, one}`).
     */
    public const EXPECT_PLURAL_ARGUMENT_SELECTOR_FRAGMENT = 'EXPECT_PLURAL_ARGUMENT_SELECTOR_FRAGMENT';

    /** Selector in `plural` or `selectordinal` is malformed (e.g. `{foo, plural, =x {#}}`) */
    public const INVALID_PLURAL_ARGUMENT_SELECTOR = 'INVALID_PLURAL_ARGUMENT_SELECTOR';

    /**
     * Duplicate selectors in `plural` or `selectordinal` argument.
     * (e.g. {foo, plural, one {#} one {#}}).
     */
    public const DUPLICATE_PLURAL_ARGUMENT_SELECTOR = 'DUPLICATE_PLURAL_ARGUMENT_SELECTOR';

    /** Duplicate selectors in `select` argument.
     * (e.g. {foo, select, apple {apple} apple {apple}}).
     */
    public const DUPLICATE_SELECT_ARGUMENT_SELECTOR = 'DUPLICATE_SELECT_ARGUMENT_SELECTOR';

    /** Plural or select argument option must have `other` clause. */
    public const MISSING_OTHER_CLAUSE = 'MISSING_OTHER_CLAUSE';

    /** The tag is malformed. (e.g. `<bold!>foo</bold!>) */
    public const INVALID_TAG = 'INVALID_TAG';

    /** The tag name is invalid. (e.g. `<123>foo</123>`) */
    public const INVALID_TAG_NAME = 'INVALID_TAG_NAME';

    /** The closing tag does not match the opening tag. (e.g. `<bold>foo</italic>`) */
    public const UNMATCHED_CLOSING_TAG = 'UNMATCHED_CLOSING_TAG';

    /** The opening tag has unmatched closing tag. (e.g. `<bold>foo`) */
    public const UNCLOSED_TAG = 'UNCLOSED_TAG';

    private function __construct()
    {
    }
}
