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

use function Symfony\Component\String\s;

/**
 * Adapted from https://github.com/formatjs/formatjs/blob/590f1f81b26934c6dc7a55fff938df5436c6f158/packages/icu-messageformat-parser/parser.ts.
 *
 * @experimental
 */
class IntlMessageParser
{
    private string $message;
    private Position $position;
    private bool $ignoreTag;
    private bool $requiresOtherClause;

    public function __construct(
        string $message,
    ) {
        $this->message = $message;
        $this->position = new Position(0, 1, 1);
        $this->ignoreTag = true;
        $this->requiresOtherClause = true;
    }

    /**
     * @throws \Exception
     */
    public function parse(): array
    {
        return $this->parseMessage(0, '', false);
    }

    /**
     * @throws \Exception
     */
    private function parseMessage(int $nestingLevel, mixed $parentArgType, bool $expectingCloseTag): array
    {
        $elements = [];

        while (!$this->isEOF()) {
            $char = $this->char();
            if (123 === $char /* `{` */) {
                $result = $this->parseArgument($nestingLevel, $expectingCloseTag);
                if ($result['err']) {
                    return $result;
                }
                $elements[] = $result['val'];
            } elseif (125 === $char /* `}` */ && $nestingLevel > 0) {
                break;
            } elseif (
                35 === $char /* `#` */
                && ('plural' === $parentArgType || 'selectordinal' === $parentArgType)
            ) {
                $position = clone $this->position;
                $this->bump();
                $elements[] = [
                    'type' => 'pound',
                    'location' => new Location($position, clone $this->position),
                ];
            } elseif (
                60 === $char /* `<` */
                && !$this->ignoreTag
                && 47 === $this->peek() // char code for '/'
            ) {
                if ($expectingCloseTag) {
                    break;
                } else {
                    return $this->error(
                        ErrorKind::UNMATCHED_CLOSING_TAG,
                        new Location(clone $this->position, clone $this->position)
                    );
                }
            } elseif (
                60 === $char /* `<` */
                && !$this->ignoreTag
                && Utils::isAlpha($this->peek() || 0)
            ) {
                $result = $this->parseTag($nestingLevel, $parentArgType);
                if ($result['err']) {
                    return $result;
                }
                $elements[] = $result['val'];
            } else {
                $result = $this->parseLiteral($nestingLevel, $parentArgType);
                if ($result['err']) {
                    return $result;
                }
                $elements[] = $result['val'];
            }
        }

        return [
            'val' => $elements,
            'err' => null,
        ];
    }

    /**
     * This method assumes that the caller has peeked ahead for the first tag character.
     */
    private function parseTagName(): string
    {
        $startOffset = $this->offset();

        $this->bump(); // the first tag name character
        while (!$this->isEOF() && Utils::isPotentialElementNameChar($this->char())) {
            $this->bump();
        }

        return s($this->message)->slice($startOffset, $this->offset() - $startOffset)->toString();
    }

    /**
     * @return array{ val: array{ type: Type::LITERAL, value: string, location: Location }, err: null }
     */
    private function parseLiteral(int $nestingLevel, string $parentArgType): array
    {
        $start = clone $this->position;

        $value = '';
        while (true) {
            $parseQuoteResult = $this->tryParseQuote($parentArgType);
            if ($parseQuoteResult) {
                $value .= $parseQuoteResult;
                continue;
            }

            $parseUnquotedResult = $this->tryParseUnquoted($nestingLevel, $parentArgType);
            if ($parseUnquotedResult) {
                $value .= $parseUnquotedResult;
                continue;
            }

            $parseLeftAngleResult = $this->tryParseLeftAngleBracket();
            if ($parseLeftAngleResult) {
                $value .= $parseLeftAngleResult;
                continue;
            }

            break;
        }

        $location = new Location($start, clone $this->position);

        return [
            'val' => [
                'type' => Type::LITERAL,
                'value' => $value,
                'location' => $location,
            ],
            'err' => null,
        ];
    }

    private function tryParseLeftAngleBracket(): string|null
    {
        if (
            !$this->isEOF()
            && 60 === $this->char() /* `<` */
            && ($this->ignoreTag
                // If at the opening tag or closing tag position, bail.
                || !Utils::isAlphaOrSlash($this->peek() || 0))
        ) {
            $this->bump(); // `<`

            return '<';
        }

        return null;
    }

    /**
     * Starting with ICU 4.8, an ASCII apostrophe only starts quoted text if it immediately precedes
     * a character that requires quoting (that is, "only where needed"), and works the same in
     * nested messages as on the top level of the pattern. The new behavior is otherwise compatible.
     */
    private function tryParseQuote(string $parentArgType): string|null
    {
        if ($this->isEOF() || 39 !== $this->char() /* `'` */) {
            return null;
        }

        // Parse escaped char following the apostrophe, or early return if there is no escaped char.
        // Check if is valid escaped character
        switch ($this->peek()) {
            case 39 /* `'` */ :
                // double quote, should return as a single quote.
                $this->bump();

                $this->bump();

                return "'";
                // '{', '<', '>', '}'
            case 123:
            case 60:
            case 62:
            case 125:
                break;
            case 35: // '#'
                if ('plural' === $parentArgType || 'selectordinal' === $parentArgType) {
                    break;
                }

                return null;
            default:
                return null;
        }

        $this->bump(); // apostrophe
        $codePoints = [$this->char()]; // escaped char
        $this->bump();

        // read chars until the optional closing apostrophe is found
        while (!$this->isEOF()) {
            $ch = $this->char();
            if (39 === $ch /* `'` */) {
                if (39 === $this->peek() /* `'` */) {
                    $codePoints[] = 39;
                    // Bump one more time because we need to skip 2 characters.
                    $this->bump();
                } else {
                    // Optional closing apostrophe.
                    $this->bump();
                    break;
                }
            } else {
                $codePoints[] = $ch;
            }
            $this->bump();
        }

        return Utils::fromCodePoint(...$codePoints);
    }

    private function tryParseUnquoted(
        int $nestingLevel,
        string $parentArgType
    ): string|null {
        if ($this->isEOF()) {
            return null;
        }
        $ch = $this->char();

        if (
            60 === $ch /* `<` */
            || 123 === $ch /* `{` */
            || (35 === $ch /* `#` */
                && ('plural' === $parentArgType || 'selectordinal' === $parentArgType))
            || (125 === $ch /* `}` */ && $nestingLevel > 0)
        ) {
            return null;
        } else {
            $this->bump();

            return Utils::fromCodePoint($ch);
        }
    }

    /**
     * @return Result
     */
    private function parseArgument(
        int $nestingLevel,
        bool $expectingCloseTag
    ): array {
        $openingBracePosition = clone $this->position;
        $this->bump(); // `{`

        $this->bumpSpace();

        if ($this->isEOF()) {
            return $this->error(
                ErrorKind::EXPECT_ARGUMENT_CLOSING_BRACE,
                new Location($openingBracePosition, clone $this->position)
            );
        }

        if (125 === $this->char() /* `}` */) {
            $this->bump();

            return $this->error(
                ErrorKind::EMPTY_ARGUMENT,
                new Location($openingBracePosition, clone $this->position)
            );
        }

        // argument name
        $value = $this->parseIdentifierIfPossible()['value'];
        if (!$value) {
            return $this->error(
                ErrorKind::MALFORMED_ARGUMENT,
                new Location($openingBracePosition, clone $this->position)
            );
        }

        $this->bumpSpace();

        if ($this->isEOF()) {
            return $this->error(
                ErrorKind::EXPECT_ARGUMENT_CLOSING_BRACE,
                new Location($openingBracePosition, clone $this->position)
            );
        }

        switch ($this->char()) {
            // Simple argument: `{name}`
            case 125 /* `}` */ :
                $this->bump(); // `}`

                return [
                    'val' => [
                        'type' => Type::ARGUMENT,
                        'value' => $value,
                        'location' => new Location($openingBracePosition, clone $this->position),
                    ],
                    'err' => null,
                ];

                // Argument with options: `{name, format, ...}`
            case 44 /* `,` */ :
                $this->bump(); // `,`
                $this->bumpSpace();

                if ($this->isEOF()) {
                    return $this->error(
                        ErrorKind::EXPECT_ARGUMENT_CLOSING_BRACE,
                        new Location($openingBracePosition, clone $this->position)
                    );
                }

                return $this->parseArgumentOptions(
                    $nestingLevel,
                    $expectingCloseTag,
                    $value,
                    $openingBracePosition
                );

            default:
                return $this->error(
                    ErrorKind::MALFORMED_ARGUMENT,
                    new Location($openingBracePosition, clone $this->position)
                );
        }
    }

    /**
     * Advance the parser until the end of the identifier, if it is currently on
     * an identifier character. Return an empty string otherwise.
     *
     * @return array{ value: string, location: Location}
     */
    private function parseIdentifierIfPossible(): array
    {
        $startingPosition = clone $this->position;

        $startOffset = $this->offset();
        $value = Utils::matchIdentifierAtIndex($this->message, $startOffset);
        $endOffset = $startOffset + s($value)->length();

        $this->bumpTo($endOffset);

        $endPosition = clone $this->position;
        $location = new Location($startingPosition, $endPosition);

        return ['value' => $value, 'location' => $location];
    }

    private function parseArgumentOptions(
        int $nestingLevel,
        bool $expectingCloseTag,
        string $value,
        Position $openingBracePosition
    ): array {
        // Parse this range:
        // {name, type, style}
        //        ^---^
        $typeStartPosition = clone $this->position;
        $argType = $this->parseIdentifierIfPossible()['value'];
        $typeEndPosition = clone $this->position;

        switch ($argType) {
            case '':
                // Expecting a style string number, date, time, plural, selectordinal, or select.
                return $this->error(
                    ErrorKind::EXPECT_ARGUMENT_TYPE,
                    new Location($typeStartPosition, $typeEndPosition)
                );
            case 'number':
            case 'date':
            case 'time':
                // Parse this range:
                // {name, number, style}
                //              ^-------^
                $this->bumpSpace();
                /** @var array{style: string, styleLocation: Location}|null */
                $styleAndLocation = null;

                if ($this->bumpIf(',')) {
                    $this->bumpSpace();

                    $styleStartPosition = clone $this->position;
                    $result = $this->parseSimpleArgStyleIfPossible();
                    if ($result['err']) {
                        return $result;
                    }
                    $style = s($result['val'])->trimEnd();

                    if (0 === $style->length()) {
                        return $this->error(
                            ErrorKind::EXPECT_ARGUMENT_STYLE,
                            new Location(clone $this->position, clone $this->position)
                        );
                    }

                    $styleLocation = new Location(
                        $styleStartPosition,
                        clone $this->position
                    );
                    $styleAndLocation = [
                        'style' => $style->toString(),
                        'styleLocation' => $styleLocation,
                    ];
                }

                $argCloseResult = $this->tryParseArgumentClose($openingBracePosition);
                if ($argCloseResult['err']) {
                    return $argCloseResult;
                }

                $location = new Location(
                    $openingBracePosition,
                    clone $this->position
                );

                // Extract style or skeleton
                if ($styleAndLocation && s($styleAndLocation['style'] ?? '')->startsWith('::')) {
                    // Skeleton starts with `::`.
                    $skeleton = s($styleAndLocation['style'])->slice(2)->trimStart()->toString();

                    if ('number' === $argType) {
                        $result = $this->parseNumberSkeletonFromString(
                            $skeleton,
                            $styleAndLocation['styleLocation']
                        );
                        if ($result['err']) {
                            return $result;
                        }

                        return [
                            'val' => [
                                'type' => Type::NUMBER,
                                'value' => $value,
                                'location' => $location,
                                'style' => $result['val'],
                            ],
                            'err' => null,
                        ];
                    } else {
                        if (0 === s($skeleton)->length()) {
                            return $this->error(ErrorKind::EXPECT_DATE_TIME_SKELETON, $location);
                        }

                        $dateTimePattern = $skeleton;

                        $style = [
                            'type' => SkeletonType::DATE_TIME,
                            'pattern' => $dateTimePattern,
                            'location' => $styleAndLocation['styleLocation'],
                            'parsedOptions' => [],
                        ];

                        $type = 'date' === $argType ? Type::DATE : Type::TIME;

                        return [
                            'val' => [
                                'type' => $type,
                                'value' => $value,
                                'location' => $location,
                                'style' => $style,
                            ],
                            'err' => null,
                        ];
                    }
                }

                // Regular style or no style.
                return [
                    'val' => [
                        'type' => 'number' === $argType ? Type::NUMBER : ('date' === $argType ? Type::DATE : Type::TIME),
                        'value' => $value,
                        'location' => $location,
                        'style' => $styleAndLocation['style'] ?? null,
                    ],
                    'err' => null,
                ];

            case 'plural':
            case 'selectordinal':
            case 'select':
                // Parse this range:
                // {name, plural, options}
                //              ^---------^
                $typeEndPosition = clone $this->position;
                $this->bumpSpace();

                if (!$this->bumpIf(',')) {
                    return $this->error(
                        ErrorKind::EXPECT_SELECT_ARGUMENT_OPTIONS,
                        new Location($typeEndPosition, clone $typeEndPosition)
                    );
                }
                $this->bumpSpace();

                // Parse offset:
                // {name, plural, offset:1, options}
                //                ^-----^
                //
                // or the first option:
                //
                // {name, plural, one {...} other {...}}
                //                ^--^
                $identifierAndLocation = $this->parseIdentifierIfPossible();

                $pluralOffset = 0;
                if ('select' !== $argType && 'offset' === $identifierAndLocation['value']) {
                    if (!$this->bumpIf(':')) {
                        return $this->error(
                            ErrorKind::EXPECT_PLURAL_ARGUMENT_OFFSET_VALUE,
                            new Location(clone $this->position, clone $this->position)
                        );
                    }
                    $this->bumpSpace();
                    $result = $this->tryParseDecimalInteger(
                        ErrorKind::EXPECT_PLURAL_ARGUMENT_OFFSET_VALUE,
                        ErrorKind::INVALID_PLURAL_ARGUMENT_OFFSET_VALUE
                    );
                    if ($result['err']) {
                        return $result;
                    }

                    // Parse another identifier for option parsing
                    $this->bumpSpace();
                    $identifierAndLocation = $this->parseIdentifierIfPossible();

                    $pluralOffset = $result['val'];
                }

                $optionsResult = $this->tryParsePluralOrSelectOptions(
                    $nestingLevel,
                    $argType,
                    $expectingCloseTag,
                    $identifierAndLocation
                );
                if ($optionsResult['err']) {
                    return $optionsResult;
                }

                $argCloseResult = $this->tryParseArgumentClose($openingBracePosition);
                if ($argCloseResult['err']) {
                    return $argCloseResult;
                }

                $location = new Location(
                    $openingBracePosition,
                    clone $this->position
                );

                if ('select' === $argType) {
                    return [
                        'val' => [
                            'type' => Type::SELECT,
                            'value' => $value,
                            'options' => $optionsResult['val'],
                            'location' => $location,
                        ],
                        'err' => null,
                    ];
                } else {
                    return [
                        'val' => [
                            'type' => Type::PLURAL,
                            'value' => $value,
                            'offset' => $pluralOffset,
                            'options' => $optionsResult['val'],
                            'pluralType' => 'plural' === $argType ? 'cardinal' : 'ordinal',
                            'location' => $location,
                        ],
                        'err' => null,
                    ];
                }

                // no break
            default:
                return $this->error(
                    ErrorKind::INVALID_ARGUMENT_TYPE,
                    new Location($typeStartPosition, $typeEndPosition)
                );
        }
    }

    private function tryParseArgumentClose(
        Position $openingBracePosition
    ): array {
        // Parse: {value, number, ::currency/GBP }
        //
        if ($this->isEOF() || 125 !== $this->char() /* `}` */) {
            return $this->error(
                ErrorKind::EXPECT_ARGUMENT_CLOSING_BRACE,
                new Location($openingBracePosition, clone $this->position)
            );
        }
        $this->bump(); // `}`

        return ['val' => null, 'err' => null];
    }

    /**
     * See: https://github.com/unicode-org/icu/blob/af7ed1f6d2298013dc303628438ec4abe1f16479/icu4c/source/common/messagepattern.cpp#L659.
     */
    private function parseSimpleArgStyleIfPossible(): array
    {
        $nestedBraces = 0;

        $startPosition = clone $this->position;
        while (!$this->isEOF()) {
            $ch = $this->char();
            switch ($ch) {
                case 39 /* `'` */ :
                    // Treat apostrophe as quoting but include it in the style part.
                    // Find the end of the quoted literal text.
                    $this->bump();

                    $apostrophePosition = clone $this->position;

                    if (!$this->bumpUntil("'")) {
                        return $this->error(
                            ErrorKind::UNCLOSED_QUOTE_IN_ARGUMENT_STYLE,
                            new Location($apostrophePosition, clone $this->position)
                        );
                    }
                    $this->bump();
                    break;

                case 123 /* `{` */ :
                    ++$nestedBraces;
                    $this->bump();
                    break;

                case 125 /* `}` */ :
                    if ($nestedBraces > 0) {
                        --$nestedBraces;
                    } else {
                        return [
                            'val' => s($this->message)->slice($startPosition->offset, $this->offset() - $startPosition->offset)->toString(),
                            'err' => null,
                        ];
                    }
                    break;

                default:
                    $this->bump();
                    break;
            }
        }

        return [
            'val' => s($this->message)->slice($startPosition->offset, $this->offset() - $startPosition->offset)->toString(),
            'err' => null,
        ];
    }

    private function parseNumberSkeletonFromString(
        string $skeleton,
        Location $location
    ) {
        $tokens = [];

        return [
            'val' => [
                'type' => Type::NUMBER,
                'tokens' => $tokens,
                'location' => $location,
                'parsedOptions' => [],
            ],
            'err' => null,
        ];
    }

    /**
     * @param number nesting_level The current nesting level of messages.
     *     This can be positive when parsing message fragment in select or plural argument options.
     * @param string parent_arg_type The parent argument's type
     * @param bool parsed_first_identifier If provided, this is the first identifier-like selector of
     *     the argument. It is a by-product of a previous parsing attempt.
     * @param array{value: string, location: Location} expecting_close_tag If true, this message is directly or indirectly nested inside
     *     between a pair of opening and closing tags. The nested message will not parse beyond
     *     the closing tag boundary.
     */
    private function tryParsePluralOrSelectOptions(
        int $nestingLevel,
        string $parentArgType,
        bool $expectCloseTag,
        array $parsedFirstIdentifier
    ): array {
        $hasOtherClause = false;
        $options = [];
        $parsedSelectors = [];
        ['value' => $selector, 'location' => $selectorLocation] = $parsedFirstIdentifier;

        // Parse:
        // one {one apple}
        // ^--^
        while (true) {
            if ('' === $selector) {
                $startPosition = clone $this->position;
                if ('select' !== $parentArgType && $this->bumpIf('=')) {
                    // Try parse `={number}` selector
                    $result = $this->tryParseDecimalInteger(
                        ErrorKind::EXPECT_PLURAL_ARGUMENT_SELECTOR,
                        ErrorKind::INVALID_PLURAL_ARGUMENT_SELECTOR
                    );
                    if ($result['err']) {
                        return $result;
                    }
                    $selectorLocation = new Location($startPosition, clone $this->position);
                    $selector = s($this->message)->slice($startPosition->offset, $this->offset() - $startPosition->offset)->toString();
                } else {
                    break;
                }
            }

            // Duplicate selector clauses
            if (\in_array($selector, $parsedSelectors, true)) {
                return $this->error(
                    'select' === $parentArgType
                        ? ErrorKind::DUPLICATE_SELECT_ARGUMENT_SELECTOR
                        : ErrorKind::DUPLICATE_PLURAL_ARGUMENT_SELECTOR,
                    $selectorLocation
                );
            }

            if ('other' === $selector) {
                $hasOtherClause = true;
            }

            // Parse:
            // one {one apple}
            //     ^----------^
            $this->bumpSpace();
            $openingBracePosition = clone $this->position;
            if (!$this->bumpIf('{')) {
                return $this->error(
                    'select' === $parentArgType
                        ? ErrorKind::EXPECT_SELECT_ARGUMENT_SELECTOR_FRAGMENT
                        : ErrorKind::EXPECT_PLURAL_ARGUMENT_SELECTOR_FRAGMENT,
                    new Location(clone $this->position, clone $this->position)
                );
            }

            $fragmentResult = $this->parseMessage(
                $nestingLevel + 1,
                $parentArgType,
                $expectCloseTag
            );
            if ($fragmentResult['err']) {
                return $fragmentResult;
            }
            $argCloseResult = $this->tryParseArgumentClose($openingBracePosition);
            if ($argCloseResult['err']) {
                return $argCloseResult;
            }

            $options[$selector] = [
                'value' => $fragmentResult['val'],
                'location' => new Location($openingBracePosition, clone $this->position),
            ];

            // Keep track of the existing selectors
            $parsedSelectors[] = $selector;

            // Prep next selector clause.
            $this->bumpSpace();
            ['value' => $selector, 'location' => $selectorLocation] = $this->parseIdentifierIfPossible();
        }

        if (0 === \count($options)) {
            return $this->error(
                'select' === $parentArgType
                    ? ErrorKind::EXPECT_SELECT_ARGUMENT_SELECTOR
                    : ErrorKind::EXPECT_PLURAL_ARGUMENT_SELECTOR,
                new Location(clone $this->position, clone $this->position)
            );
        }

        if ($this->requiresOtherClause && !$hasOtherClause) {
            return $this->error(
                ErrorKind::MISSING_OTHER_CLAUSE,
                new Location(clone $this->position, clone $this->position)
            );
        }

        return [
            'val' => $options,
            'err' => null,
        ];
    }

    /**
     * @param ErrorKind::* $expectNumberError
     * @param ErrorKind::* $invalidNumberError
     */
    private function tryParseDecimalInteger(
        string $expectNumberError,
        string $invalidNumberError,
    ): array {
        $sign = 1;
        $startingPosition = clone $this->position;

        if ($this->bumpIf('+')) {
            // no-op
        } elseif ($this->bumpIf('-')) {
            $sign = -1;
        }

        $hasDigits = false;
        $decimal = 0;
        while (!$this->isEOF()) {
            $ch = $this->char();
            if ($ch >= 48 /* `0` */ && $ch <= 57 /* `9` */) {
                $hasDigits = true;
                $decimal = $decimal * 10 + ($ch - 48);
                $this->bump();
            } else {
                break;
            }
        }

        $location = new Location($startingPosition, clone $this->position);

        if (!$hasDigits) {
            return $this->error($expectNumberError, $location);
        }

        $decimal *= $sign;
        if (!Utils::isSafeInteger($decimal)) {
            return $this->error($invalidNumberError, $location);
        }

        return [
            'val' => $decimal,
            'err' => null,
        ];
    }

    private function offset(): int
    {
        return $this->position->offset;
    }

    private function isEOF(): bool
    {
        return $this->offset() === s($this->message)->length();
    }

    /**
     * Return the code point at the current position of the parser.
     * Throws if the index is out of bound.
     *
     * @throws \Exception
     */
    private function char(): int
    {
        $message = s($this->message);

        $offset = $this->position->offset;
        if ($offset >= $message->length()) {
            throw new \OutOfBoundsException();
        }

        $code = $message->slice($offset, 1)->codePointsAt(0)[0] ?? null;
        if (null === $code) {
            throw new \Exception("Offset {$offset} is at invalid UTF-16 code unit boundary");
        }

        return $code;
    }

    /**
     * @param ErrorKind::*
     *
     * @return array{ val: null, err: array{ kind: ErrorKind::*, location: Location, message: string } }
     */
    private function error(string $kind, Location $location): array
    {
        return [
            'val' => null,
            'err' => [
                'kind' => $kind,
                'location' => $location,
                'message' => $this->message,
            ],
        ];
    }

    /**
     * Bump the parser to the next UTF-16 code unit.
     */
    private function bump(): void
    {
        if ($this->isEOF()) {
            return;
        }
        $code = $this->char();
        if (10 === $code /* '\n' */) {
            ++$this->position->line;
            $this->position->column = 1;
            ++$this->position->offset;
        } else {
            ++$this->position->column;
            ++$this->position->offset;
        }
    }

    /**
     * If the substring starting at the current position of the parser has
     * the given prefix, then bump the parser to the character immediately
     * following the prefix and return true. Otherwise, don't bump the parser
     * and return false.
     */
    private function bumpIf(string $prefix): bool
    {
        if (s($this->message)->slice($this->offset())->startsWith($prefix)) {
            for ($i = 0, $len = \strlen($prefix); $i < $len; ++$i) {
                $this->bump();
            }

            return true;
        }

        return false;
    }

    /**
     * Bump the parser until the pattern character is found and return `true`.
     * Otherwise bump to the end of the file and return `false`.
     */
    private function bumpUntil(string $pattern): bool
    {
        $currentOffset = $this->offset();
        $index = s($this->message)->indexOf($pattern, $currentOffset);
        if ($index >= 0) {
            $this->bumpTo($index);

            return true;
        } else {
            $this->bumpTo(s($this->message)->length());

            return false;
        }
    }

    /**
     * Bump the parser to the target offset.
     * If target offset is beyond the end of the input, bump the parser to the end of the input.
     *
     * @throws \Exception
     */
    private function bumpTo(int $targetOffset)
    {
        if ($this->offset() > $targetOffset) {
            throw new \Exception(sprintf('targetOffset %s must be greater than or equal to the current offset %d', $targetOffset, $this->offset()));
        }

        $targetOffset = min($targetOffset, s($this->message)->length());
        while (true) {
            $offset = $this->offset();
            if ($offset === $targetOffset) {
                break;
            }
            if ($offset > $targetOffset) {
                throw new \Exception("targetOffset {$targetOffset} is at invalid UTF-16 code unit boundary");
            }

            $this->bump();
            if ($this->isEOF()) {
                break;
            }
        }
    }

    /** advance the parser through all whitespace to the next non-whitespace code unit. */
    private function bumpSpace()
    {
        while (!$this->isEOF() && Utils::isWhiteSpace($this->char())) {
            $this->bump();
        }
    }

    /**
     * Peek at the *next* Unicode codepoint in the input without advancing the parser.
     * If the input has been exhausted, then this returns null.
     */
    private function peek(): ?int
    {
        if ($this->isEOF()) {
            return null;
        }

        $code = $this->char();
        $offset = $this->offset();
        $nextCodes = s($this->message)->codePointsAt($offset + ($code >= 0x10000 ? 2 : 1));

        return $nextCodes[0] ?? null;
    }
}
