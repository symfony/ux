<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Twig;

use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @author Matheo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 */
class PropsTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $parser = $this->parser;
        $stream = $parser->getStream();

        $names = [];
        $values = [];
        while (!$stream->nextIf(Token::BLOCK_END_TYPE)) {
            $name = $stream->expect(Token::NAME_TYPE)->getValue();

            if ($stream->nextIf(Token::OPERATOR_TYPE, '=')) {
                $values[$name] = $parser->getExpressionParser()->parseExpression();
            }

            $names[] = $name;

            if (!$stream->nextIf(Token::PUNCTUATION_TYPE)) {
                $stream->expect(Token::BLOCK_END_TYPE);
                break;
            }
        }

        return new PropsNode($names, $values, $token->getLine(), $token->getValue());
    }

    public function getTag(): string
    {
        return 'props';
    }
}
