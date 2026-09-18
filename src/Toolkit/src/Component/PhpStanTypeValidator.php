<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Component;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\ParserException;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

/**
 * Validates that a prop type is a syntactically valid PHPStan/PHPDoc type, using
 * phpstan/phpdoc-parser. Only the syntax is checked (e.g. balanced generics, no stray
 * unions); identifiers such as `boolean` or `number` are accepted as-is.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PhpStanTypeValidator
{
    private readonly Lexer $lexer;
    private readonly TypeParser $typeParser;

    public function __construct()
    {
        $config = new ParserConfig([]);
        $this->lexer = new Lexer($config);
        $this->typeParser = new TypeParser($config, new ConstExprParser($config));
    }

    public function isValid(string $type): bool
    {
        try {
            $tokens = new TokenIterator($this->lexer->tokenize($type));
            $this->typeParser->parse($tokens);
            // Ensure the whole token was a single, complete type with no leftover input.
            $tokens->consumeTokenType(Lexer::TOKEN_END);

            return true;
        } catch (ParserException) {
            return false;
        }
    }
}
